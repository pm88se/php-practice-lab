<?php

declare(strict_types=1);

namespace Practice;

use ReflectionClass;

require_once __DIR__ . '/HandlesEvent.php';

final class AttributeEventDispatcher
{
    private array $listeners;
    private array $scannedClasses;
    public function __construct()
    {
        $this->listeners = [];
        $this->scannedClasses = [];
    }
    /**
     * Discover public methods marked #[HandlesEvent(...)] and register them.
     *
     * PHP-005: implement discovery with Reflection and cache method metadata
     * per handler class, without retaining handler instances in the metadata cache.
     */
    public function register(object $handler): void
    {
        $reflection = new ReflectionClass($handler);

        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        $this->scannedClasses[get_class($handler)] = true;
        
        $eventsToRegister = [];
        foreach ($methods as $method) {
            $attributes = $method->getAttributes(HandlesEvent::class);
            foreach ($attributes as $attribute) {
                $instance = $attribute->newInstance();
                if (isset($this->listeners[$instance->name]) || isset($eventsToRegister[$instance->name])) {
                    throw new \LogicException('PHP-005 duplicate event registration rejected');
                }
                $eventsToRegister[$instance->name] = $method;
            }            
        }
        foreach ($eventsToRegister as $event => $method) {
            $this->listeners[$event] = ['method' => $method, 'handler' => $handler];
        }
    }

    /**
     * Route an event to the registered handler method.
     * Throw \InvalidArgumentException for an unknown event.
     */
    public function dispatch(string $event, array $payload): mixed
    {
        if (!isset($this->listeners[$event])) {
            throw new \InvalidArgumentException('PHP-005 unknown event');
        }
        $dispatcher = $this->listeners[$event];
        return $dispatcher['method']->invoke($dispatcher['handler'], $payload);
    }

    /**
     * Number of distinct classes whose attribute metadata was scanned.
     * Exposed only to make the reflection-cache property testable.
     */
    public function scannedClassCount(): int
    {
        return count($this->scannedClasses);
    }
}
