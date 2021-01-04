<?php

namespace OpenDominion\Helpers;

use OpenDominion\Models\Race;

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

    public function getLandTypesByBuildingType(Race $race): array
    {

      if($race->name == 'Dragon')
      {
        $buildings = [
          'tower' => 'mountain',
          'farm' => 'mountain',
          'ore_mine' => 'mountain',
          'gem_mine' => 'mountain',
          'lumberyard' => 'forest',
          'guard_tower' => 'hill',
        ];
      }
      elseif($race->name == 'Merfolk')
      {
        $buildings = [
          'farm' => 'water',
          'tower' => 'water',
          'temple' => 'water',
          'gem_mine' => 'water',
          'shrine' => 'water',
        ];
      }
      elseif($race->name == 'Void')
      {
        $buildings = [
          'ziggurat' => 'mountain'
        ];
      }
      elseif($race->name == 'Growth')
      {
        $buildings = [
          'tissue' => 'swamp'
        ];
      }
      elseif($race->name == 'Myconid')
      {
        $buildings = [
          'mycelia' => 'forest'
        ];
      }
      else
      {
        $buildings = [
            'alchemy' => 'plain',
            'farm' => 'plain',
            'smithy' => 'plain',
            'masonry' => 'plain',
            'ore_mine' => 'mountain',
            'gryphon_nest' => 'mountain',
            'tower' => 'swamp',
            'wizard_guild' => 'swamp',
            'temple' => 'swamp',
            'gem_mine' => 'mountain',
            'lumberyard' => 'forest',
            'forest_haven' => 'forest',
            'factory' => 'hill',
            'guard_tower' => 'hill',
            'shrine' => 'forest',
            'barracks' => 'hill',
            'dock' => 'water',
        ];
      }

        $buildings = (['home' => $race->home_land_type] + $buildings);

        return $buildings;
    }

    public function getLandTypeIconHtml(string $landType): string
    {
        switch ($landType) {
            case 'plain':
                return '<i class="ra ra-grass-patch text-green"></i>';

            case 'mountain':
                return '<i class="fa fa-mountain text-blue"></i>';

            case 'swamp':
                return '<i class="ra ra-skull text-black"></i>';

            case 'forest':
                return '<i class="fa fa-tree text-green"></i>';

            case 'hill':
                return '<i class="ra ra-grass text-green"></i>';

            case 'water':
                return '<i class="fas fa-water text-aqua"></i>';

            default:
                return '';
        }
    }
}
