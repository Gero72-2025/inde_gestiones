<?php

namespace App\Exceptions;

use CodeIgniter\Exceptions\FrameworkException;
use CodeIgniter\Exceptions\HTTPExceptionInterface;

class PageForbiddenException extends FrameworkException implements HTTPExceptionInterface
{
    public static function forPageForbidden(?string $message = null): self
    {
        return new self($message ?? 'Forbidden', 403);
    }
}