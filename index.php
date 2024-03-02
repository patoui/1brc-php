<?php

declare(strict_types=1);

/**
 * Format:
 * key = station name
 * value = [
 *     'count' => 111,
 *     'total' => 189100,
 *     'min' => 1,
 *     'max' => 321,
 * ]
 */
$stations = [];

function lines($file): Generator
{
    $f = fopen($file, 'r');

    try {
        while ($line = fgets($f)) {
            yield $line;
        }
    } finally {
        fclose($f);
    }
}

$c = 0;

foreach (lines(__DIR__.'/measurements.txt') as $measurement) {
    [$name, $temperature] = explode(';', trim($measurement));

    if (!isset($stations[$name])) {
        $stations[$name] = [
            'count' => 0,
            'total' => 0,
            // max value of 99.99
            'min' => 100,
            // min value of -99.99
            'max' => -100,
        ];
    }

    ++$stations[$name]['count'];
    $stations[$name]['total'] += (float) $temperature;

    if ($temperature < $stations[$name]['min']) {
        $stations[$name]['min'] = (float) $temperature;
    }

    if ($temperature > $stations[$name]['max']) {
        $stations[$name]['max'] = (float) $temperature;
    }

    ++$c;

    if ($c % 10_000_000 === 0) {
        echo $c . PHP_EOL;
    }
}

$filename = __DIR__.'/output.txt';
file_put_contents($filename, '{');

// sort by station name
ksort($stations);

$last = array_key_last($stations);

foreach ($stations as $name => $data) {
    file_put_contents(
        $filename,
        sprintf(
            "%s=%.1f/%.1f/%.1f" . ($name === $last ? '' : ','),
            $name,
            $data['min'],
            $data['total'] / $data['count'],
            $data['max']
        ),
        FILE_APPEND
    );
}

file_put_contents($filename, '}', FILE_APPEND);
