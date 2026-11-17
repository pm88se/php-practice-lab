<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/AtomicJsonSnapshotWriter.php';

use Practice\AtomicJsonSnapshotWriter;

$folder = sys_get_temp_dir() . '/php009-' . bin2hex(random_bytes(8));
if (!mkdir($folder, 0700) && !is_dir($folder)) {
    throw new RuntimeException('Could not create PHP-009 test directory');
}
$path = $folder . '/settings.json';
$writer = new AtomicJsonSnapshotWriter();

try {
    $initial = ['tenant_id' => 7, 'flags' => ['returns' => true, 'new_ui' => false], 'version' => 1];
    $writer->write($path, $initial);
    expectSame($initial, json_decode((string)file_get_contents($path), true, 512, JSON_THROW_ON_ERROR), 'php009 first snapshot');

    $updated = ['tenant_id' => 7, 'flags' => ['returns' => false], 'version' => 2];
    $writer->write($path, $updated);
    expectSame($updated, json_decode((string)file_get_contents($path), true, 512, JSON_THROW_ON_ERROR), 'php009 replacement');

    $before = file_get_contents($path);
    $thrown = false;
    try {
        $writer->write($path, ['bad' => NAN]);
    } catch (JsonException $error) {
        $thrown = true;
    }
    expectSame(true, $thrown, 'php009 serialization fails');
    expectSame($before, file_get_contents($path), 'php009 existing snapshot survives failure');

    $leftovers = glob($folder . '/.snapshot-*');
    expectSame([], $leftovers === false ? [] : $leftovers, 'php009 temporary files cleaned up');

    // A failed write target must not silently succeed.
    $failed = false;
    try {
        $writer->write($folder . '/missing/subfolder.json', ['version' => 3]);
    } catch (Throwable $error) {
        $failed = true;
    }
    expectSame(true, $failed, 'php009 write failure is reported');

    echo "PHP-009 OK\n";
} finally {
    foreach (glob($folder . '/*') ?: [] as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    foreach (glob($folder . '/.snapshot-*') ?: [] as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    rmdir($folder);
}
