<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Environment.php';

use Practice\Environment;

fwrite(STDOUT, Environment::name() . PHP_EOL);
