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

$input = __DIR__.'/measurements.txt';
// $input = __DIR__.'/top1000.txt';
foreach (lines($input) as $measurement) {
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

$station_count = 0;
$output = '';

foreach ($stations as $name => $data) {
    $output .= $name . '='
        . $data['min']
        . '/' . number_format($data['total'] / $data['count'], 1)
        . '/' . $data['max']
        . ($name === $last ? '' : ', ');

    ++$station_count;

    if ($station_count >= 1000) {
        file_put_contents($filename, $output, FILE_APPEND);
        $output = '';
        $station_count = 0;
    }
}

if ($station_count > 0) {
    file_put_contents($filename, $output, FILE_APPEND);
}

file_put_contents($filename, '}', FILE_APPEND);
