<?php
$config = config('game.asteroids');

$min_distance = $config['station_to_station_distance'] ?? 8000;
$universe_border_distance = $config['universe_size'] / 10;
$asteroid_min_distance = $config['min_distance_between_asteroids'] ?? 0;

return [
  'min_distance' => $min_distance,
  'universe_size' => $config['universe_size'],
  'asteroid_min_distance' => $asteroid_min_distance,
  'universe_border_distance' => $universe_border_distance,
];
