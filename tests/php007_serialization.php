<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/ImportCheckpoint.php';

use Practice\ImportCheckpoint;

$checkpoint = new ImportCheckpoint('import-42', 12);
$checkpoint->advance(3);

$stream = fopen('php://temp', 'w+');
if ($stream === false) {
    throw new RuntimeException('PHP-007 could not open test stream');
}

try {
    $checkpoint->attachStream($stream);
    expectSame(true, $checkpoint->hasStream(), 'PHP-007 stream attached');

    expectSame(
        ['importId' => 'import-42', 'processed' => 15],
        $checkpoint->__serialize(),
        'PHP-007 durable payload excludes stream',
    );

    $encoded = serialize($checkpoint);
    expectSame(false, str_contains($encoded, 'stream'), 'PHP-007 serialized form excludes transient field');

    $restored = unserialize($encoded, ['allowed_classes' => [ImportCheckpoint::class]]);
    expectSame(true, $restored instanceof ImportCheckpoint, 'PHP-007 restored type');
    expectSame('import-42', $restored->importId, 'PHP-007 readonly import ID restored');
    expectSame(15, $restored->processed(), 'PHP-007 progress restored');
    expectSame(false, $restored->hasStream(), 'PHP-007 stream not restored');

    // A restored object remains usable and can attach a new process-local resource.
    $restored->advance(5);
    expectSame(20, $restored->processed(), 'PHP-007 restored object stays mutable where intended');

    // Cloning should preserve durable data but detach the live resource.
    $cloned = clone $checkpoint;
    expectSame('import-42', $cloned->importId, 'PHP-007 clone copies readonly ID');
    expectSame(15, $cloned->processed(), 'PHP-007 clone copies progress');
    expectSame(false, $cloned->hasStream(), 'PHP-007 clone detaches runtime stream');
    expectSame(true, $checkpoint->hasStream(), 'PHP-007 original retains stream after clone');
    $cloned->advance(10);
    expectSame(25, $cloned->processed(), 'PHP-007 clone can advance independently');
    expectSame(15, $checkpoint->processed(), 'PHP-007 original progress unchanged');

    // __unserialize should also work on an object initialized without calling
    // the usual constructor, including initialization of a readonly property.
    $reflection = new ReflectionClass(ImportCheckpoint::class);
    $withoutConstructor = $reflection->newInstanceWithoutConstructor();
    $withoutConstructor->__unserialize(['importId' => 'import-99', 'processed' => 7]);
    expectSame('import-99', $withoutConstructor->importId, 'PHP-007 readonly initialized in __unserialize');
    expectSame(7, $withoutConstructor->processed(), 'PHP-007 manually restored progress');
    expectSame(false, $withoutConstructor->hasStream(), 'PHP-007 transient field cleared in __unserialize');
} finally {
    fclose($stream);
}
