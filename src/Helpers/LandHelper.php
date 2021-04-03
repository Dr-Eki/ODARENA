<?php

namespace OpenDominion\Helpers;

use OpenDominion\Models\Race;
use OpenDominion\Models\Building;

class LandHelper
{
    public function getLandTypes(): array
    {
        return [
            'plain',
            'mountain',
            'swamp',
            'forest',
            'hill',
            'water',
        ];
    }

    public function getLandTypeForBuildingByRace(string $building, Race $race): string
    {
        return $this->getLandTypesByBuildingType($race)[$building];
    }

    public function getLandTypeIconHtml(string $landType): string
    {
        switch ($landType)
        {
            case 'plain':
                return '<i class="ra ra-grass-patch ra-fw text-green"></i>';

            case 'mountain':
                return '<i class="fa fa-mountain fa-fw text-blue"></i>';

            case 'swamp':
                return '<i class="fas fa-frog fa-fw text-purple"></i>';

            case 'forest':
                return '<i class="fa fa-tree fa-fw text-green"></i>';

            case 'hill':
                return '<i class="ra ra-grass fa-fw text-green"></i>';

            case 'water':
                return '<i class="fas fa-water fa-fw text-aqua"></i>';

            default:
                return '';
        }
    }
}
