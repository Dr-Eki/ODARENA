<?php

namespace OpenDominion\Services\Dominion;

use DB;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Traits\DominionGuardsTrait;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Resource;
use OpenDominion\Calculators\Dominion\DesecrationCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Services\Dominion\QueueService;

class DesecrationService
{
    use DominionGuardsTrait;

    protected $queueService;
    protected $desecrationCalculator;
    protected $militaryCalculator;

    protected $desecrationEvent;

    protected $desecration = [
        'game_event_id' => '',
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
        $this->militaryCalculator = app(MilitaryCalculator::class);

        $this->queueService = app(QueueService::class);
    }

    public function desecrate(Dominion $desecrator, array $desecratingUnits, GameEvent $battlefield): array
    {

        $this->guardLockedDominion($desecrator);

        DB::transaction(function () use ($desecrator, $desecratingUnits, $battlefield) {

            // Checks
            if(!$desecrator->round->getSetting('invasions'))
            {
                throw new GameException('Invasions are disabled this round.');
            }

            if(!$desecrator->race->getPerkValue('can_desecrate'))
            {
                throw new GameException('You cannot desecrate.');
            }

            if($desecrator->protection_ticks > 0)
            {
                throw new GameException('You cannot desecrate while under protection.');
            }

            if($desecrator->round->id !== $battlefield->source->round_id)
            {
                throw new GameException('Invalid battlefield.');
            }

            if(!in_array($battlefield->type, ['invasion', 'barbarian_invasion']))
            {
                throw new GameException('Invalid battlefield. Unsupported type.');
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


            if(!$this->desecrationCalculator->getAvailableBattlefields($desecrator)->contains($battlefield))
            {
                throw new GameException('Invalid battlefield. It may be too old to desecrate.');
            }  

            $this->desecration['game_event_id'] = $battlefield->id;
            $this->desecration['units_sent'] = $desecratingUnits;
            $this->desecration['units_returning'] = $desecratingUnits;

            $this->desecration['bodies'] = [
                'available' => $battlefield->data['result']['bodies']['available'],
                'desecrated' => 0,
            ];

            // Desecrate
            $this->desecration['bodies']['desecrated'] = $this->desecrationCalculator->getBodiesDesecrated($desecrator, $desecratingUnits, $battlefield);

            $desecrationResult = $this->desecrationCalculator->getDesecrationResult($desecrator, $this->desecration['units_sent'], $this->desecration['bodies']['desecrated']);

            

            if(!isset($this->desecration['result']['resource_key']))
            {
                dd("Sacré bleu! A bug! Don't desecrate this battlefield again.")
                #dd($this->desecration, $desecrationResult);
            }

            $resource = Resource::where('key', $this->desecration['result']['resource_key'])->first();

            $this->desecration['result']['resource_key'] = key($desecrationResult);
            $this->desecration['result']['resource_name'] = $resource->name; #Resource::where('key', $this->desecration['result']['resource_key'])->firstOrFail()->name;
            $this->desecration['result']['amount'] = $desecrationResult[key($desecrationResult)];

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

            $queueDatas[] = ['resource_'.$this->desecration['result']['resource_key'] => $this->desecration['result']['amount']];

            $ticks = $this->desecrationCalculator->isOwnRealmDesecration($desecrator, $battlefield) ? 2 : 12;

            foreach($queueDatas as $queueData)
            {
                $this->queueService->queueResources(
                    'desecration',
                    $desecrator,
                    $queueData,
                    $ticks
                );    
            }

            $data = $battlefield->data;
            $data['result']['bodies']['desecrated'] += $this->desecration['bodies']['desecrated'];
            $data['result']['bodies']['available'] -= $this->desecration['bodies']['desecrated'];
            $data['result']['bodies']['available'] = max(0, $data['result']['bodies']['available']);
            $battlefield->data = $data;
            
            $battlefield->save();

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
            #ldd($this->desecration);

        });

        $message = sprintf(
            'Your units arrive at the battlefield, desecrating %s %s and returning with %s %s.',
            number_format($this->desecration['bodies']['desecrated']),
            str_plural('body', $this->desecration['bodies']['desecrated']),
            number_format($this->desecration['result']['amount']),
            str_plural($this->desecration['result']['resource_name'], $this->desecration['result']['amount'])
        );

        $alertType = ($this->desecration['bodies']['desecrated'] > 0 ? 'success' : 'warning');

        return [
            'message' => $message,
            'alert-type' => $alertType,
            'redirect' => route('dominion.event', [$this->desecrationEvent->id])
        ];


        return $this->desecration;
    }

}
