<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use function Amp\async;

/**
 * Station format:
 * key = station name
 * value = [
 *     'count' => 111,
 *     'total' => 189100,
 *     'min' => 1,
 *     'max' => 321,
 * ]
 */

$input = __DIR__ . '/measurements.txt';
// $input = __DIR__ . "/top1000000.txt";
// $input = __DIR__ . "/top1000.txt";

$process_chunk = static function ($chunk) {
    $stations = [];
    $parsed = preg_split('/[\n;]/', $chunk);
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
    }

    return $stations;
};

$futures = [];

$f = fopen($input, 'r');
$pos = 0;
$limit = 750;
$stations = [];

while (!feof($f)) {
    if (count($futures) > $limit) {
        foreach ($futures as $future) {
            $future_stations = $future->await();
            foreach ($future_stations as $name => $future_station) {
                if (isset($stations[$name])) {
                    $station = &$stations[$name];
                    $station['count'] += $future_station['count'];
                    $station['total'] += $future_station['total'];
                    if ($future_station['min'] < $station['min']) {
                        $station['min'] = $future_station['min'];
                    }
                    if ($future_station['max'] > $station['max']) {
                        $station['max'] = $future_station['max'];
                    }
                } else {
                    $stations[$name] = $future_station;
                }
            }
        }
        $futures = [];
    }

    fseek($f, $pos);

    $data = fread($f, 5242880);

    $last_pos = strrpos($data, "\n");

    if ($last_pos === false) {
        $last_pos = strlen($data);
    }

    //$futures[] = $runtime->run($process_chunk, substr($data, 0, $last_pos));
    $futures[] = async($process_chunk, substr($data, 0, $last_pos));

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
