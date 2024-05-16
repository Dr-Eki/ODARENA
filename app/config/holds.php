<?php

# app/config/holds.php

return [
    'starting_land' => 250,
    'starting_morale' => 100,
    'starting_peasants' => 5000,
    'starting_defense' => 25000,

    'tick_discover_hold_chance' => 40,

    'defaults' => [
        'unit_return_ticks' => 12,
        'unit_training_ticks' => 12,
        'construction_ticks' => 12,
        'rezoning_ticks' => 12,
        'queue_ticks' => 12,
    ],

    'luxury_resource_production' =>
    [
        # Per getBestMatchingBuilding() in HoldHelper.php
        'books' => 0.02,
        'figurines' => 0.04,
        'instruments' => 0.016,
        'spices' => 0.016,
        'sugar' => 0.015,
    ],

    # Sentiment values
    'sentiment_values' =>
    [
        'sold_amount_reduced' => 20,
        'discovered_by_dominion' => 100,
        'trade_route_established' => 4,
        'trade_completed' => 1,
    ],

    'sentiment_divisor' => 4000,

];