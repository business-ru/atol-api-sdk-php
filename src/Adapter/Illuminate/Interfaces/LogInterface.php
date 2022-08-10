<?php

namespace Atol\Api\Adapter\IlluminateAtolApi\Interfaces;

interface LogInterface
{
    public function info(string $message, array $context = []): void;

    public function error(string $message, array $context = []): void;

    public function warning(string $message, array $context = []): void;

    public function critical(string $message, array $context = []): void;
}
