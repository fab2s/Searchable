<?php

/*
 * This file is part of fab2s/searchable.
 * (c) Fabrice de Stefanis / https://github.com/fab2s/Searchable
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

require __DIR__ . '/vendor/autoload.php';

use fab2s\Searchable\Phonetic\Phonetic;
use fab2s\Searchable\Phonetic\Soundex2;
use fab2s\Searchable\Tests\PhoneticTest;

// Extract all test words from the data provider
$words      = array_column(PhoneticTest::encodeProvider(), 0);
$count      = count($words);
$iterations = 1000;
$total      = $count * $iterations;

$encoders = [
    'metaphone' => fn (string $w) => metaphone($w),
    'Soundex2'  => Soundex2::encode(...),
    'Phonetic'  => Phonetic::encode(...),
];

$results = [];

foreach ($encoders as $name => $encoder) {
    // Warmup
    foreach ($words as $w) {
        $encoder($w);
    }

    // Benchmark
    $shuffled = $words;
    $t0       = hrtime(true);
    for ($i = 0; $i < $iterations; $i++) {
        shuffle($shuffled);
        foreach ($shuffled as $w) {
            $encoder($w);
        }
    }
    $elapsed = (hrtime(true) - $t0) / 1e9;

    $results[$name] = [
        'time'       => $elapsed,
        'per'        => $elapsed / $total * 1e6,
        'throughput' => (int) ($total / $elapsed),
    ];
}

// Output markdown table
printf("Benchmark: %d words x %d iterations = %s encodes\n\n", $count, $iterations, number_format($total));
printf("| %-12s | %10s | %10s | %14s |\n", 'Encoder', 'Time (s)', 'Per word', 'Words/sec');
printf("|%s|%s|%s|%s|\n", str_repeat('-', 14), str_repeat('-', 12), str_repeat('-', 12), str_repeat('-', 16));

foreach ($results as $name => $r) {
    printf(
        "| %-12s | %9.3f s | %7.1f µs | %14s |\n",
        $name,
        $r['time'],
        $r['per'],
        number_format($r['throughput']),
    );
}
