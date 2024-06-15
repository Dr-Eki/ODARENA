<?php

namespace OpenDominion\Services\Dominion;

use DB;
use Illuminate\Support\Str;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Traits\DominionGuardsTrait;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Resource;
use OpenDominion\Calculators\Dominion\DesecrationCalculator;
use OpenDominion\Calculators\Dominion\MagicCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\Dominion\ResourceService;
use OpenDominion\Services\Dominion\StatsService;

class DesecrationService
{
    use DominionGuardsTrait;

    protected $desecrationCalculator;
    protected $magicCalculator;
    protected $militaryCalculator;
    protected $queueService;
    protected $resourceService;
    protected $statsServices;

    protected $desecrationEvent;

    protected $desecration = [
        'units_returning' => [],
        'units_sent' => [],
        'bodies' => [
            'available' => 0,
            'desecrated' => 0,
        ],
        'result' => [
            'amount' => 0,
            'resource_name' => '',
        ],
    ];

    public function __construct()
    {
        $this->desecrationCalculator = app(DesecrationCalculator::class);
        $this->magicCalculator = app(MagicCalculator::class);
        $this->militaryCalculator = app(MilitaryCalculator::class);

        $this->queueService = app(QueueService::class);
        $this->resourceService = app(ResourceService::class);
        $this->statsServices = app(StatsService::class);
    }

    public function desecrate(Dominion $desecrator, array $desecratingUnits): array
    {

        $this->guardLockedDominion($desecrator);

        DB::transaction(function () use ($desecrator, $desecratingUnits) {

            // Checks
            if(!$desecrator->race->getPerkValue('can_desecrate'))
            {
                throw new GameException('You cannot desecrate.');
            }

            if($desecrator->protection_ticks > 0)
            {
                throw new GameException('You cannot desecrate while under protection.');
            }

            $unitsWithDesecrationPerk = 0;
            foreach($desecratingUnits as $slot => $amount)
            {
                if($desecrator->race->getUnitPerkValueForUnitSlot($slot, 'desecration'))
                {
                    $unitsWithDesecrationPerk += $amount;
                }
            }

            if(!$unitsWithDesecrationPerk)
            {
                throw new GameException('At least some units sent must be capable of desecration.');
            }

            if($this->magicCalculator->getWizardPoints($desecrator) < $this->magicCalculator->getWizardPointsRequiredToSendUnits($desecrator, $desecratingUnits))
            {
                throw new GameException('You do not have enough wizard points to send these units.');
            }

            $this->desecration['units_sent'] = $desecratingUnits;
            $this->desecration['units_returning'] = $desecratingUnits;

            $this->desecration['bodies'] = [
                'available' => $desecrator->round->resource_bodies,
                'desecrated' => 0,
            ];

            // Desecrate
            $this->desecration['bodies']['desecrated'] = $this->desecrationCalculator->getBodiesDesecrated($desecrator, $desecratingUnits);

            $desecrationResult = $this->desecrationCalculator->getDesecrationResult($desecrator, $this->desecration['units_sent']);

            // Remove units
            foreach($desecratingUnits as $slot => $amount)
            {
                $desecrator->{'military_unit'.$slot} -= $amount;
            }
            // Generate queue data
            foreach($desecratingUnits as $slot => $amount)
            {
                $queueDatas[] = ['military_unit'.$slot => $amount];
            }

            if(empty($desecrationResult))
            {
                #throw new GameException('No resources were desecrated.');
            }
            else
            {
                $this->desecration['result']['resource_key'] = key($desecrationResult);

                $resource = Resource::where('key', $this->desecration['result']['resource_key'])->first();
    
                $this->desecration['result']['resource_name'] = $resource->name; #Resource::where('key', $this->desecration['result']['resource_key'])->firstOrFail()->name;
                $this->desecration['result']['amount'] = $desecrationResult[key($desecrationResult)];

                $queueDatas[] = ['resource_'.$this->desecration['result']['resource_key'] => $this->desecration['result']['amount']];

                $ticks = 8;

                foreach($queueDatas as $queueData)
                {
                    $this->queueService->queueResources(
                        'desecration',
                        $desecrator,
                        $queueData,
                        $ticks
                    );    
                }

                # Update round resources (remove bodies)
                $this->resourceService->updateRoundResources($desecrator->round, [
                    'body' => -$this->desecration['bodies']['desecrated'],
                ]);

            }

            $this->desecrationEvent = GameEvent::create([
                'round_id' => $desecrator->round_id,
                'source_type' => Dominion::class,
                'source_id' => $desecrator->id,
                'target_type' => NULL,
                'target_id' => NULL,
                'type' => 'desecration',
                'data' => $this->desecration,
                'tick' => $desecrator->round->ticks
            ]);

            $desecrator->save(['event' => HistoryService::EVENT_ACTION_DESECRATION]);
    
            # Debug before saving:
            #ldd($this->desecration, $desecrator->round->resources, $message);

        });

        if($this->desecration['bodies']['desecrated'] > 0)
        {
            $message = sprintf(
                'Your units desecrate %s %s and begin their journey home with %s %s.',
                number_format($this->desecration['bodies']['desecrated']),
                Str::plural('body', $this->desecration['bodies']['desecrated']),
                number_format($this->desecration['result']['amount']),
                Str::plural($this->desecration['result']['resource_name'], $this->desecration['result']['amount'])
            );
    
            $alertType = 'success';

            $this->statsServices->updateStat($desecrator, 'body_desecrated', $this->desecration['bodies']['desecrated']);
            $this->statsServices->updateStat($desecrator, 'desecrations', 1);

        }
        else
        {
            $message = 'Your units desecrate nothing.';
            $alertType = 'warning';
        }


        return [
            'message' => $message,
            'alert-type' => $alertType,
            'redirect' => route('dominion.event', [$this->desecrationEvent->id])
        ];

        return $this->desecration;
    }

}
