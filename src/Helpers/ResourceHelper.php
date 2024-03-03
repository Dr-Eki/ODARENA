<?php

namespace OpenDominion\Helpers;

use Illuminate\Support\Collection;
use OpenDominion\Models\Race;
use OpenDominion\Models\Resource;
use OpenDominion\Models\Deity;

class ResourceHelper
{

    public function isResource(string $resourceKey): bool
    {
        return Resource::where('key', $resourceKey)->first() ? true : false;
    }

    public function getResourceName(string $resourceKey): string
    {
        if(!$this->isResource($resourceKey))
        {
            return '?';
        }

        return Resource::where('key', $resourceKey)->firstOrFail()->name;
    }

    public function getResourceConsumptionTerm(string $resourceKey): string
    {
        switch ($resourceKey) {

            case 'food':
                $string = 'Eaten';
                break;

            case 'mana':
                $string = 'Drained';
                break;

            case 'ore':
                $string = 'Refined';
                break;

            case 'brimmer':
                $string = 'Spent';
                break;

            case 'prisoner':
                $string = 'Deceased';
                break;

            default:
                $string = 'Consumed';

        }


        return $string;
    }

    public function getResourcesByRace(Race $race): Collection
    {
        return $race->resources;

        $deities = collect(Resource::all()->keyBy('key')->sortBy('name')->where('enabled',1));

        foreach($deities as $deity)
        {
          if(
                (count($deity->excluded_races) > 0 and in_array($race->name, $deity->excluded_races)) or
                (count($deity->exclusive_races) > 0 and !in_array($race->name, $deity->exclusive_races))
            )
          {
              $deities->forget($deity->key);
          }
        }

        return $deities;
    }

    public function getExclusivityString(Deity $deity): string
    {

        $exclusivityString = '<br><small class="text-muted">';

        if($exclusives = count($deity->exclusive_races))
        {
            foreach($deity->exclusive_races as $raceName)
            {
                $exclusivityString .= $raceName;
                if($exclusives > 1)
                {
                    $exclusivityString .= ', ';
                }
                $exclusives--;
            }

            $exclusivityString .= ' only';
        }
        elseif($excludes = count($deity->excluded_races))
        {
            $exclusivityString .= 'All except ';
            foreach($deity->excluded_races as $raceName)
            {
                $exclusivityString .= $raceName;
                if($excludes > 1)
                {
                    $exclusivityString .= ', ';
                }
                $excludes--;
            }
        }

        $exclusivityString .= '</small>';

        return $exclusivityString;
    }

    public function getRacesByResource(Resource $resource): Collection
    {
        return Race::wherein('playable',[1,2])->where('resources', 'LIKE', ('%' . $resource->key .'%'))->get();
    }

}
