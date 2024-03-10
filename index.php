<?php

declare(strict_types=1);

/**
 * Format:
 * key = station name
 * value = [
 *     0 => 111, // count
 *     1 => 189100, // total
 *     2 => 1, // min
 *     3 => 321, // max
 * ]
 */
$stations = [];

// $c = 0;

$f = fopen(__DIR__ . '/measurements.txt', 'r');
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
                0 => 0,
                1 => 0,
                // max value of 99.99
                2 => 100,
                // min value of -99.99
                3 => -100,
            ];
        }
        $station = &$stations[$name];

        ++$station[0];
        $station[1] += $temperature;

        if ($temperature < $station[2]) {
            $station[2] = $temperature;
        }

        if ($temperature > $station[3]) {
            $station[3] = $temperature;
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
        $station[2] .
        '/' .
        sprintf('%.1f', $station[1] / $station[0]) .
        '/' .
        $station[3] . ', ';
}

echo rtrim($output, ', ') . '}';
