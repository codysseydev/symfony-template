<?php

declare(strict_types=1);

namespace Tests\Architecture;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\Description;
use Arkitect\Expression\Expression;
use Arkitect\Rules\Violation;
use Arkitect\Rules\ViolationMessage;
use Arkitect\Rules\Violations;
use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Checked against the real, autoloaded class rather than the parsed AST:
 * ClassDescription does not follow the inheritance chain, and attributes are
 * not inherited by PHP itself.
 *
 * A class passes when it, or any parent, carries Symfony's #[WithHttpStatus]
 * attribute, or when it implements HttpExceptionInterface (which Symfony's
 * HttpException does). A module base exception that carries the attribute
 * therefore covers every exception under it.
 */
final class HasHttpStatus implements Expression
{
    public function describe(ClassDescription $theClass, string $because): Description
    {
        return new Description(
            "{$theClass->getName()} should carry #[WithHttpStatus], inherit it from its module base exception, or implement HttpExceptionInterface",
            $because,
        );
    }

    public function evaluate(ClassDescription $theClass, Violations $violations, string $because): void
    {
        $fqcn = $theClass->getFQCN();

        if ($this->carriesHttpStatus($fqcn) || is_a($fqcn, HttpExceptionInterface::class, true)) {
            return;
        }

        $violations->add(Violation::create(
            $fqcn,
            ViolationMessage::selfExplanatory($this->describe($theClass, $because)),
            $theClass->getFilePath(),
        ));
    }

    private function carriesHttpStatus(string $fqcn): bool
    {
        if (!class_exists($fqcn)) {
            return false;
        }

        for ($class = new \ReflectionClass($fqcn); false !== $class; $class = $class->getParentClass()) {
            if ([] !== $class->getAttributes(WithHttpStatus::class)) {
                return true;
            }
        }

        return false;
    }
}
