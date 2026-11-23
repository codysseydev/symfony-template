<?php

declare(strict_types=1);

namespace Tests\Architecture\Fixtures;

use Symfony\Component\HttpKernel\Exception\HttpException;

final class HttpFixtureException extends HttpException
{
    public function __construct()
    {
        parent::__construct(404);
    }
}
