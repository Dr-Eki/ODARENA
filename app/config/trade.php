<?php

# app/config/trade.php

return [
    'price_decimals' => 6,

    'desired_resource_start_value' => 100000,
    'sold_resource_start_value' => 1000000,

    'resource_base_supply_goal' => [
        'strategic' => 2000000,
        'luxury' => 2000000,
    ],
    'resource_per_tick_supply_goal' => [
        'strategic' => 10000,
        'luxury' => (5/4)*10000,
    ],

    'sentiment_divisor' => 1100,

    'trade_base_max' => 10000,

];