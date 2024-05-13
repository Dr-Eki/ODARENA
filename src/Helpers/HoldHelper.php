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
            2 => 'annexed',
            3 => 'abandoned',
            4 => 'razed',
        ];

        return $statusDescriptions[$status] ?? 'Unknown';
    }

    public function getBestMatchingBuilding(string $resourceKey): string
    {
        $resourceMap = collect([
            'acid' => 'tissue',
            'ash' => 'ore_mine',
            'blood' => 'altar',
            'body' => 'altar',
            'books' => 'school',
            'brimmer' => 'refinery',
            'figurines' => 'workshop',
            'food' => 'farm',
            'gems' => 'gem_mine',
            'gloom' => 'night_tower',
            'gold' => 'gold_mine',
            'gunpowder' => 'powder_mill',
            'horse' => 'stable',
            'instruments' => 'workshop',
            'kelp' => 'wharf',
            'lumber' => 'saw_mill',
            'magma' => 'ore_mine',
            'mana' => 'tower',
            'miasma' => 'mass_grave',
            'mud' => 'harbour',
            'obsidian' => 'gem_mine',
            'ore' => 'ore_mine',
            'pearls' => 'wharf',
            'prisoner' => 'constabulary',
            'souls' => 'altar',
            'spices' => 'farm',
            'sugar' => 'farm',
            'swamp_gas' => 'tower',
            'thunderstone' => 'dwargen_mine',
            'yak' => 'yakstead'
        ]);
        
        return $resourceMap->get($resourceKey, 'harbour');
    }

}
