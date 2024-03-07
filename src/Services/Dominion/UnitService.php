<?php

namespace OpenDominion\Services\Dominion;

use DB;
use Carbon\Carbon;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionUnit;
use OpenDominion\Models\Unit;
use OpenDominion\Calculators\Dominion\UnitCalculator;
use OpenDominion\Helpers\ResourceHelper;
use OpenDominion\Services\Dominion\QueueService;

class ResourceService
{

    /** @var ResourceHelper */
    protected $resourceHelper;

    /** @var ResourceCalculator */
    protected $resourceCalculator;

    /** @var QueueService */
    protected $queueService;

    public function __construct()
    {
        $this->resourceHelper = app(ResourceHelper::class);
        $this->queueService = app(QueueService::class);
    }

    public function addUnits(Dominion $dominion, array $units, int $state): void
    {
        foreach($units as $unitKey => $amount)
        {
            $unit = Unit::where('key', $unitKey)->first();
            $amount = (int)abs($amount);
            $state = (int)$state;

            # Create or update DominionUnit
            DominionUnit::updateOrCreate([
                    'dominion_id' => $dominion->id,
                    'unit_id' => $unit->id,
                    'amount' => $amount,
                    'state' => $state,
            ]);
        }
    }

    public function removeUnits(Dominion $dominion, array $units): void
    {
        foreach($units as $unitKey => $amount)
        {
            $unit = Unit::where('key', $unitKey)->first();
            $amount = (int)abs($amount);

            # Update DominionUnit, delete if new amount is 0 or less
            $dominionUnit = DominionUnit::where([
                'dominion_id' => $dominion->id,
                'unit_id' => $unit->id,
            ])->first();

            if($dominionUnit)
            {
                $temporaryAmount = $dominionUnit->amount - $amount;

                if($temporaryAmount <= 0)
                {
                    $dominionUnit->delete();
                }
                else
                {
                    $dominionUnit->amount = $temporaryAmount;
                    $dominionUnit->save();
                }
            }
        }
    }

    public function changeUnitsState(Dominion $dominion, array $units, int $state): void
    {
        foreach($units as $unitKey => $amount)
        {
            $unit = Unit::where('key', $unitKey)->first();
            $amount = (int)abs($amount);
            $state = (int)$state;

            # Update DominionUnit
            DominionUnit::updateOrCreate([
                'dominion_id' => $dominion->id,
                'unit_id' => $unit->id,
                'amount' => $amount,
                'state' => $state,
            ]);
        }
    }

}
