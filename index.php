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

// $c = 0;

$input = __DIR__ . '/measurements.txt';
// $input = __DIR__ . "/top1000000.txt";

$f = fopen($input, 'r');
$pos = 0;

while (!feof($f)) {
    fseek($f, $pos);

    $data = fread($f, 5242880);

    $last_pos = strrpos($data, "\n");

    if ($last_pos === false) {
        $last_pos = strlen($data);
    }

    $parsed = preg_split('/[\n;]/', substr($data, 0, $last_pos));
    for ($i = 0; $i < count($parsed); $i += 2) {
        if ($parsed[$i] === '') {
            ++$i;
        }

        $name = $parsed[$i];
        $temperature = (float) $parsed[$i + 1];

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
        $station = &$stations[$name];

        ++$station['count'];
        $station['total'] += $temperature;

        if ($temperature < $station['min']) {
            $station['min'] = $temperature;
        }

        if ($temperature > $station['max']) {
            $station['max'] = $temperature;
        }

        // ++$c;

        // if ($c % 10_000_000 === 0) {
        //     echo $c . PHP_EOL;
        // }
    }

    $pos += $last_pos;
}

fclose($f);

// sort by station name
ksort($stations);

$output = '{';

foreach ($stations as $name => $station) {
    $output .=
        $name .
        '=' .
        $station['min'] .
        '/' .
        sprintf('%.1f', $station['total'] / $station['count']) .
        '/' .
        $station['max'] . ', ';
}

echo rtrim($output, ', ') . '}';
