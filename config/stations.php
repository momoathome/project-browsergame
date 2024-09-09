<?php
$config = config('asteroids');

$min_distance = 5000;
$universe_border_distance = 10_000;
$asteroid_min_distance = $config['min_distance'];

return [
  'min_distance' => $min_distance,
  'universe_size' => $config['universe_size'],
  'asteroid_min_distance' => $asteroid_min_distance,
  'universe_border_distance' => $universe_border_distance,
];
