<?php

declare(strict_types=1);

namespace NyonCode\WireMds\Processors;

use NyonCode\WireMds\Contracts\Processor;
use ReflectionClass;

/**
 * Base processor with common functionality.
 */
abstract class AbstractProcessor implements Processor
{
    protected int $defaultPriority = 100;

    /**
     * {@inheritdoc}
     */
    public function priority(): int
    {
        return $this->defaultPriority;
    }

    /**
     * {@inheritdoc}
     */
    public function shouldProcess(ReflectionClass $reflection): bool
    {
        return true;
    }

    /**
     * Get a single attribute instance from a class.
     * 
     * @template T
     * @param ReflectionClass $reflection
     * @param class-string<T> $attributeClass
     * @return T|null
     */
    protected function getAttribute(ReflectionClass $reflection, string $attributeClass): ?object
    {
        $attributes = $reflection->getAttributes($attributeClass);

        if (empty($attributes)) {
            return null;
        }

        return $attributes[0]->newInstance();
    }

    /**
     * Get all attribute instances of a specific type.
     * 
     * @template T
     * @param ReflectionClass $reflection
     * @param class-string<T> $attributeClass
     * @return array<T>
     */
    protected function getAttributes(ReflectionClass $reflection, string $attributeClass): array
    {
        $attributes = $reflection->getAttributes($attributeClass);

        return array_map(fn($attr) => $attr->newInstance(), $attributes);
    }

    /**
     * Check if class has a specific attribute.
     * 
     * @param ReflectionClass $reflection
     * @param class-string $attributeClass
     */
    protected function hasAttribute(ReflectionClass $reflection, string $attributeClass): bool
    {
        return !empty($reflection->getAttributes($attributeClass));
    }

    /**
     * Get the short class name without namespace.
     */
    protected function getShortClassName(ReflectionClass $reflection): string
    {
        return $reflection->getShortName();
    }

    /**
     * Convert class name to route-friendly format.
     * UserDashboard -> user-dashboard
     */
    protected function classNameToSlug(string $className): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $className));
    }

    /**
     * Get namespace segments as array.
     * 
     * @return array<string>
     */
    protected function getNamespaceSegments(ReflectionClass $reflection): array
    {
        $namespace = $reflection->getNamespaceName();
        return array_filter(explode('\\', $namespace));
    }
}
