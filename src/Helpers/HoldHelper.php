<?php

namespace OpenDominion\Helpers;

class HoldHelper
{


    public function __construct()
    {
    }

    function getSentimentDescription(int $sentiment) {
        // Define the sentiment ranges and their corresponding descriptions
        $sentimentRanges = [
            -1000 => 'despise',
            -750  => 'hate',
            -500  => 'dislike',
            -100  => 'avoidant',
            -10   => 'hesitant',
            0     => 'neutral',
            10    => 'interested',
            100   => 'respect',
            500   => 'like',
            750   => 'fond',
            1000  => 'admire'
        ];
    
        // Sort the keys in descending order to prepare for comparison
        krsort($sentimentRanges);
    
        // Determine the appropriate description based on the sentiment score
        foreach ($sentimentRanges as $key => $description) {
            if ($sentiment >= $key) {
                return $description;
            }
        }
    
        // Default case if the sentiment is below the lowest range
        return 'Unknown';
    }
    
    public function getSentimentClass(string $description): string
    {
        $classes = [
            'despise' =>    'danger',
            'hate' =>       'danger',
            'dislike' =>    'danger',
            'avoidant' =>   'warning',
            'hesitant' =>   'warning',
            'neutral' =>    'primary',
            'curious' =>    'info',
            'respect' =>    'info',
            'like' =>       'success',
            'admire' =>     'success',
            'adore' =>      'success',
        ];
    
        return $classes[$description] ?? 'primary';
    }

    public function getStatusDescription(int $status): string
    {
        $statusDescriptions = [
            0 => 'undiscovered',
            1 => 'discovered',
        ];

        return $statusDescriptions[$status] ?? 'Unknown';
    }

}
