<?php

declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

// Executes lab SQL from a file, including DDL. Use ONLY the disposable MySQL lab.
// Minimal parser: semicolon-separated statements, no procedures or semicolons in literals.
$path = $argv[1] ?? '';
if ($path === '' || !is_file($path)) {
    fwrite(STDERR, "Usage: php mysql/performance/bin/run_sql.php path/to/file.sql\n");
    exit(1);
}

$sql = file_get_contents($path);
if ($sql === false) {
    throw new RuntimeException('Unable to read the supplied SQL file');
}

$lines = array_filter(
    explode("\n", $sql),
    static fn (string $line): bool => !str_starts_with(ltrim($line), '--')
);
$statements = array_filter(
    array_map('trim', explode(';', implode("\n", $lines))),
    static fn (string $statement): bool => $statement !== ''
);

$pdo = mysqlLabConnection();

foreach ($statements as $index => $statement) {
    printf("\n--- SQL %d ---\n%s\n", $index + 1, $statement);
    $start = hrtime(true);
    $result = $pdo->query($statement);
    $elapsedMs = (hrtime(true) - $start) / 1_000_000;

    if ($result->columnCount() > 0) {
        foreach ($result->fetchAll(PDO::FETCH_NUM) as $row) {
            echo implode(" | ", array_map(
                static fn (mixed $value): string => $value === null ? 'NULL' : (string) $value,
                $row
            )), "\n";
        }
    } else {
        printf("Affected rows: %d\n", $result->rowCount());
    }
    printf("Client-side query call: %.2f ms (not a benchmark)\n", $elapsedMs);
}
