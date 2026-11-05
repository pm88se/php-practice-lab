<?php

declare(strict_types=1);

namespace Practice;

use RuntimeException;
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
     * Lazy feed of paid orders; PHP-002: replace stub with streaming implementation.
     *
     * @return \Generator<int, array{order_id: string, amount_cents: int}>
     */
    public static function paidOrders(string $path): \Generator
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new RuntimeException('Unable to open CSV file');
        }
        try {
            while (($row = fgetcsv($handle)) !== false) {
                if ($row[1] === 'paid') {
                    yield [
                        'order_id' => $row[0], 
                        'amount_cents' => $row[2] + 0
                    ];
                }
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * Return at most $limit paid orders, stopping iteration as soon as enough are found.
     *
     * @return list<array{order_id: string, amount_cents: int}>
     */
    public static function previewPaid(string $path, int $limit): array
    {
        if ($limit <= 0) {
            return [];
        }
        $result = [];
        $paidOrdersGenerator = self::paidOrders($path);

        foreach ($paidOrdersGenerator as $paidOrder) {
            $result[] = $paidOrder;

            if (count($result) >= $limit) {
                break;
            }
        }

        return $result;
        
    }

    /**
     * Read CSV orders and return:
     * ['paid_count' => int, 'paid_total_cents' => int]
     *
     * Expected columns: order_id,status,amount_cents.
     *
     * PHP-002: refactor this method to consume paidOrders() to share parsing.
     *
     * @return array{paid_count: int, paid_total_cents: int}
     */
    public static function summarize(string $path): array
    {
        $paidOrdersGenerator = self::paidOrders($path);
        $result = ['paid_count' => 0, 'paid_total_cents' => 0];
        foreach ($paidOrdersGenerator as $paidOrder) {
            $result['paid_count'] ++;
            $result['paid_total_cents'] += $paidOrder['amount_cents'];
        }
        return $result;
    }
}
