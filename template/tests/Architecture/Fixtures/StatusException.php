<?php

declare(strict_types=1);

namespace Tests\Architecture\Fixtures;

use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;

#[WithHttpStatus(409)]
class StatusException extends \DomainException {}
