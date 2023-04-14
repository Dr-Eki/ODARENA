<?php

namespace OpenDominion\Services\Dominion;

use Log;
use LogicException;
use OpenDominion\Models\GameEvent;

use OpenDominion\Services\Dominion\OpenAIService;

use OpenDominion\Helpers\EventHelper;
use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Helpers\RealmHelper;
use OpenDominion\Helpers\UnitHelper;

class GameEventService
{

    private $openAIService;
    private $eventHelper;
    private $raceHelper;
    private $realmHelper;
    private $unitHelper;

    public function __construct()
    {
        $this->eventHelper = app(EventHelper::class);
        $this->raceHelper = app(RaceHelper::class);
        $this->realmHelper = app(RealmHelper::class);
        $this->unitHelper = app(UnitHelper::class);

        $this->openAIService = app(OpenAIService::class);
    }

    public function generateInvasionStory(GameEvent $invasion): string
    {

        # Make sure it's an invasion event
        if ($invasion->type !== 'invasion') {
            Log::debug('GameEventService::generateInvasionStory() called with non-invasion event for event UUID ' . $invasion->uuid . '.');
            return '';
        }

        $data = [];

        $attacker = $invasion->source;
        $defender = $invasion->target;
        
        $data['attacker']['ruler'] = $attacker->ruler_name;
        $data['attacker']['name'] = $attacker->name;
        $data['attacker']['faction'] = $attacker->race->name;
        $data['attacker']['faction_adjective'] = $this->raceHelper->getRaceAdjective($attacker->race);
        $data['attacker']['realm_adjective'] = $this->realmHelper->getAlignmentAdjective($attacker->realm->alignment);

        # Look at invasion data of units sent, units lost, and units killed to get the full scope of slots used
        $data['attacker']['units'] = [];

        foreach($invasion->data['attacker']['units_sent'] as $slot => $amount)
        {
            $unitName = $this->unitHelper->getUnitName($slot, $attacker->race);
            $data['attacker']['units'][$unitName]['sent'] = $amount;
        }

        foreach($invasion->data['attacker']['units_lost'] as $slot => $amount)
        {
            $unit = $attacker->race->units->where('slot', $slot)->first();
            $data['attacker']['units'][$unitName]['lost'] = $amount;
        }

        foreach($invasion->data['attacker']['units_returning'] as $slot => $amount)
        {
            if()
            $unit = $attacker->race->units->where('slot', $slot)->first();
            $data['attacker']['units'][$unit->name]['returning'] = $amount;
        }

        dd($invasion->data, $data);

        $storyteller = 'chronicler';

        #$story = $this->openAIService->sendMessageAndGetCompletion($storyteller, 'The invasion of ' . $defender->name . ' by ' . $attacker->name . ' has begun.');

        return $story;

    }

}
