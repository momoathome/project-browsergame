<?php

$asteroid_count = 7000;
$asteroid_density = 30;                                     // kleinere Zahl = dichter besiedelt
$universe_size = $asteroid_count * $asteroid_density;       // Größe des Universums

return [

    /* Core */
    'building_produce_speed' => 1.0,
    'spacecraft_produce_speed' => 1.0,
    'spacecraft_flight_speed' => 1.0,

    /* Universe */
    'size' => $universe_size,                           // Größe des Universums
    'border_distance' => $universe_size / 10,           // Minimaler Abstand zum Rand des Universums um Stationen zu platzieren
    'asteroid_count' => $asteroid_count,                // Anzahl der Asteroiden
    'asteroid_density' => $asteroid_density,            // Dichte der Asteroiden (kleinere Zahl = dichter besiedelt)
    
    // Stationen
    'station_distance' => 15000,                         // Minimaler Abstand zwischen Stationen
    'asteroid_to_station_distance' => 1500,              // Minimaler Abstand von Stationen zu Asteroiden
    'station_inner_radius' => 600,                      // Radius um Stationen ohne Asteroiden
    'station_outer_radius' => 8000,                     // Radius um Stationen mit nur Low-Value Asteroiden
    
    // Sonstiges
    'default_stations' => 25,                           // Standard-Anzahl von Stationsstandorten

    /* Asteroid */
    'asteroid_distance' => 800,                         // Minimaler Abstand zwischen Asteroiden
    'extreme_asteroid_distance' => 6000,                // Minimaler Abstand zwischen 'extreme' Asteroiden
    'strategic_asteroid_count' => 10,                   // Anzahl der strategischen Asteroiden
    'strategic_asteroid_min_value' => 150,              // Mindestwert für Ressourcen der strategischen Asteroiden
    'strategic_asteroid_max_value' => 300,              // Maximalwert für Ressourcen der strategischen Asteroiden
    'strategic_asteroid_outer_radius' => 4000,         // Äußerer Radius um Stationen für strategische Asteroiden -- sollte gleich gleich sein mit initial scanner radius

];
