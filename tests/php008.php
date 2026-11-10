<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/TenantConfigOverride.php';

use Practice\TenantConfigOverride;

$resolver = new TenantConfigOverride();

$defaults = [
    'features' => ['reviews' => true, 'returns' => false],
    'limits' => ['max_exports' => 5, 'region' => 'eu'],
];
$originalDefaults = $defaults;

$tenantA = $resolver->apply($defaults, [
    'features' => ['returns' => true],
    'limits' => ['max_exports' => 20],
]);
expectSame(
    ['features' => ['reviews' => true, 'returns' => true],
     'limits' => ['max_exports' => 20, 'region' => 'eu']],
    $tenantA,
    'PHP-008 overrides only specified settings',
);
expectSame($originalDefaults, $defaults, 'PHP-008 does not mutate base config');

$tenantB = $resolver->apply($defaults, [
    'features' => ['reviews' => false],
]);
expectSame(
    ['features' => ['reviews' => false, 'returns' => false],
     'limits' => ['max_exports' => 5, 'region' => 'eu']],
    $tenantB,
    'PHP-008 tenants do not share modifications',
);
expectSame($originalDefaults, $defaults, 'PHP-008 base unchanged after two tenants');

expectSame($originalDefaults, $resolver->apply($defaults, []), 'PHP-008 no overrides');
expectSame(
    ['features' => ['enabled' => true]],
    $resolver->apply([], ['features' => ['enabled' => true]]),
    'PHP-008 a tenant may introduce a section',
);
expectSame(
    ['features' => ['reviews' => null, 'returns' => false], 'limits' => ['max_exports' => 5, 'region' => 'eu']],
    $resolver->apply($defaults, ['features' => ['reviews' => null]]),
    'PHP-008 explicit null is a valid override',
);

// An ordinary array assignment is copy-on-write; nested scalar settings
// should not change each other after callers mutate the resulting arrays.
$tenantA['features']['reviews'] = false;
$tenantA['limits']['region'] = 'us';
expectSame($originalDefaults, $defaults, 'PHP-008 tenant mutations do not change defaults');
expectSame(false, $tenantB['features']['reviews'], 'PHP-008 tenant B keeps its own settings');
expectSame('eu', $tenantB['limits']['region'], 'PHP-008 tenant B region unaffected');

// A PHP array can carry a reference slot. Copy-on-write alone is insufficient
// to detach a reference that was introduced by a previous by-reference foreach.
$externalFlag = true;
$referencedDefaults = [
    'features' => ['reviews' => &$externalFlag, 'returns' => false],
];
$tenantC = $resolver->apply($referencedDefaults, ['features' => ['returns' => true]]);
$tenantC['features']['reviews'] = false;
expectSame(true, $externalFlag, 'PHP-008 output must not mutate a referenced source value');
expectSame(true, $referencedDefaults['features']['reviews'], 'PHP-008 referenced defaults preserved');

$overrideFlag = true;
$referencedOverrides = ['features' => ['returns' => &$overrideFlag]];
$tenantD = $resolver->apply($defaults, $referencedOverrides);
$tenantD['features']['returns'] = false;
expectSame(true, $overrideFlag, 'PHP-008 output must not alias override reference');

// The method must not leave by-reference foreach variables dangling inside
// the class, and all later calls must remain independent.
$tenantE = $resolver->apply($defaults, ['limits' => ['max_exports' => 99]]);
expectSame(true, $tenantE['features']['reviews'], 'PHP-008 subsequent call does not corrupt values');
expectSame(99, $tenantE['limits']['max_exports'], 'PHP-008 subsequent call works');
expectSame($originalDefaults, $defaults, 'PHP-008 base still unchanged');
