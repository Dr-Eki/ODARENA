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
    
    /*
    public function getSentimentDescription(int $sentiment): string
    {


        if($sentiment < -60)
        {
            return 'despise';
        }
        if($sentiment < -40)
        {
            return 'hate';
        }
        if($sentiment < -20)
        {
            return 'dislike';
        }
        if($sentiment < -10)
        {
            return 'avoidant';
        }
        if($sentiment < 0)
        {
            return 'hesitant';
        }
        if($sentiment == 0)
        {
            return 'neutral';
        }
        if($sentiment < 10)
        {
            return 'curious';
        }
        if($sentiment < 100)
        {
            return 'respect';
        }
        if($sentiment < 500)
        {
            return 'like';
        }
        if($sentiment < 750)
        {
            return 'admire';
        }
        if($sentiment < 1000)
        {
            return 'fond';
        }
        if($sentiment >= 1000)
        {
            return 'adore';
        }

        return 'undefined';
    }
    */
    
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
    
    

}
