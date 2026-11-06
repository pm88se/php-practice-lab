<?php

declare(strict_types=1);

namespace Practice;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final class HandlesEvent
{
    public function __construct(public readonly string $name)
    {
    }
}
