<?php

declare(strict_types=1);

namespace Practice;

/**
 * Apply tenant-specific settings without mutating or sharing reference slots
 * with the application defaults.
 */
final class TenantConfigOverride
{
    /**
     * Each top-level key identifies a settings section (e.g. 'features').
     * Each section is an associative array of scalar or null setting values.
     *
     * Override only keys supplied by the tenant, preserving all other values.
     * Do not implement recursive/deep merging: exactly two levels are supported.
     * Return an independent result even if inputs contain PHP reference slots.
     *
     * @param array<string, array<string, scalar|null>> $defaults
     * @param array<string, array<string, scalar|null>> $overrides
     * @return array<string, array<string, scalar|null>>
     */
    public function apply(array $defaults, array $overrides): array
    {
        $result = [];
        foreach ($defaults as $section => $settings) {
            foreach ($settings as $name => $value) {
                $result[$section][$name] = $value;
            }
        }

        foreach ($overrides as $section => $settings) {
            foreach ($settings as $name => $value) {
                $result[$section][$name] = $value;
            }
        }

        return $result;
    }
}
