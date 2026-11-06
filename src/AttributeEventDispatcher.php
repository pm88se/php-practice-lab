<?php

declare(strict_types=1);

namespace Practice;

require_once __DIR__ . '/HandlesEvent.php';

final class AttributeEventDispatcher
{
    /**
     * Discover public methods marked #[HandlesEvent(...)] and register them.
     *
     * PHP-005: implement discovery with Reflection and cache method metadata
     * per handler class, without retaining handler instances in the metadata cache.
     */
    public function register(object $handler): void
    {
        throw new \LogicException('PHP-005 register() not implemented yet');
    }

    /**
     * Route an event to the registered handler method.
     * Throw \InvalidArgumentException for an unknown event.
     */
    public function dispatch(string $event, array $payload): mixed
    {
        throw new \LogicException('PHP-005 dispatch() not implemented yet');
    }

    /**
     * Number of distinct classes whose attribute metadata was scanned.
     * Exposed only to make the reflection-cache property testable.
     */
    public function scannedClassCount(): int
    {
        throw new \LogicException('PHP-005 scannedClassCount() not implemented yet');
    }
}
