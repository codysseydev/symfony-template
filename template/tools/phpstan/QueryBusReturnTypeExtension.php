<?php

declare(strict_types=1);

namespace Tools\PHPStan;

use Framework\Bus\QueryBus;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * Teaches PHPStan that `QueryBus::ask($query)` returns the type that the
 * query's handler declares from `__invoke()`.
 *
 * `QueryBus::ask()` is declared `: mixed`, so a typed result DTO would lose its
 * type the moment it crosses the bus, and every controller would need an
 * `instanceof` check.
 *
 * Resolution follows the naming standard: `{Action}Query` is answered by
 * `{Action}QueryHandler`, so the handler class is the query class name with a
 * `Handler` suffix. When no such handler exists, this returns null and the
 * default `mixed` stands.
 */
final readonly class QueryBusReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function __construct(private ReflectionProvider $reflectionProvider) {}

    public function getClass(): string
    {
        return QueryBus::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return 'ask' === $methodReflection->getName();
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): ?Type {
        $args = $methodCall->getArgs();

        if ([] === $args) {
            return null;
        }

        $returnTypes = [];

        foreach ($scope->getType($args[0]->value)->getObjectClassNames() as $queryClass) {
            $handlerClass = $queryClass . 'Handler';

            if (!$this->reflectionProvider->hasClass($handlerClass)) {
                continue;
            }

            $handler = $this->reflectionProvider->getClass($handlerClass);

            if (!$handler->hasNativeMethod('__invoke')) {
                continue;
            }

            $returnTypes[] = $handler->getNativeMethod('__invoke')->getOnlyVariant()->getReturnType();
        }

        return [] === $returnTypes ? null : TypeCombinator::union(...$returnTypes);
    }
}
