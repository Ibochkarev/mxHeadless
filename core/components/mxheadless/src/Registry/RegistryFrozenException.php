<?php

declare(strict_types=1);

namespace MxHeadless\Registry;

final class RegistryFrozenException extends \RuntimeException
{
    public function __construct(string $message = 'Object registry is frozen')
    {
        parent::__construct($message);
    }
}
