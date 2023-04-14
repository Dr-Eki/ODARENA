<?php

namespace OpenDominion\Helpers;

use OpenDominion\Models\Terrain;
use OpenDominion\Models\Building;

class TerrainHelper
{
    public function getTerrains()
    {
        # Return all terrain names
        return Terrain::all()->pluck('name');
    }
}
