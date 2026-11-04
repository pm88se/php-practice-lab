<?php

declare(strict_types=1);

namespace Practice;

use SplFileObject;

final class CsvOrderSummary
{
    private static function openCsv(string $filePath): SplFileObject {
        $file = new SplFileObject($filePath);
        $file->setFlags(
            SplFileObject::READ_CSV |
            SplFileObject::SKIP_EMPTY
        );

        return $file;
    }

    /** Uploading full file in memory. Bad practice. */
    private static function naiveOpenCsv(string $filePath): array {
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        $data = array_map('str_getcsv', $lines);
        return $data;
    }
    /**
     * Read CSV orders and return:
     * ['paid_count' => int, 'paid_total_cents' => int]
     *
     * Expected columns: order_id,status,amount_cents.
     *
     * TODO (PHP-001): implement using bounded memory.
     *
     * @return array{paid_count: int, paid_total_cents: int}
     */
    public static function summarize(string $path): array
    {
        $file = self::openCsv($path);
        // $file = self::naiveOpenCsv($path);
        $result = ['paid_count' => 0, 'paid_total_cents' => 0];
        foreach ($file as $row) {
            if ($row[1] === 'paid') {
                $result['paid_count'] ++;
                $result['paid_total_cents'] += $row[2];
            }
        }
        return $result;
    }
}
