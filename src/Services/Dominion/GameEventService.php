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

        $data = $this->getDataArrayFromInvasion($invasion);

        $storyteller = 'You are a chronicler, writing a brief description of this battle in a medieval fantasy game, noting the land conquered (if the attacker if victorious) and briefly describing the units fighting. ';
        $storyteller .= 'Summarize unit numbers into "hundreds" for amounts less than 1000. '; 
        $storyteller .= 'Summarize unit numbers into "thousands" for amounts 1000-50000. ';
        $storyteller .= 'Summarize unit numbers into "tens of thousands" for amounts 50000 or greater. ';
        $storyteller .= 'The audience for this is 18 and older, so feel free to use graphic details (blood, gore), but do so in a tasteful manner. ';

        $invasionSummary = vsprintf(
            "An army from the %s dominion of %s led by %s (the attacker) has invaded the %s dominion of %s commaneded by %s (the defender). The battle is %s won by %s.The attacker's units were {%s} and the defender's units were {%s}.",
            [
                $data['attacker']['faction_adjective'],
                $data['attacker']['name'],
                $data['attacker']['ruler'],
                $data['defender']['faction_adjective'],
                $data['defender']['name'],
                $data['defender']['ruler'],
                $data['win_type'],
                $data['winner'],
                json_encode($data['attacker']['units']),
                json_encode($data['defender']['units'])
            ]
            );

        if($invasion->data['result']['success'])
        {
            $invasionSummary .= ' The attacker conquered ' . $invasion->data['data']['land_conquered'] . ' acres of land from the defender.';
        }

        $story = $this->openAIService->sendMessageAndGetCompletion($storyteller, $invasionSummary);
        
        return $story['assistantMessage'];
    }

    public function generateInvasionImage(GameEvent $invasion): string
    {
        $imageTypes = [
            'medieval fantasy painting',
            'black and white sketch',
        ];

        #$prompt = 'A ' . $imageTypes[array_rand($imageTypes)] . ' of the battle between ';
        $prompt = 'A detailed high quality concept art digital painting of a battle between ';
        $prompt .= $invasion->source->race->name . ' army of and ' . $invasion->target->race->name . ' army ';
        $prompt .= 'in a medieval fantasy setting.';

        return '';

        #$image = $this->openAIService->generateImagesFromText($prompt);
        #return $image['data'][0]['b64_json'];
    }

    public function getDataArrayFromInvasion(GameEvent $invasion): array
    {

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
            $unitName = $this->unitHelper->getUnitName($slot, $attacker->race);
            $data['attacker']['units'][$unitName]['lost'] = $amount;
        }

        foreach($invasion->data['attacker']['units_returning'] as $slot => $amount)
        {
            if($amount > 0)
            {
                $unitName = $this->unitHelper->getUnitName($slot, $attacker->race);
                $data['attacker']['units'][$unitName]['returning'] = $amount;    
            }
        }
        
        $data['defender']['ruler'] = $defender->ruler_name;
        $data['defender']['name'] = $defender->name;
        $data['defender']['faction'] = $defender->race->name;
        $data['defender']['faction_adjective'] = $this->raceHelper->getRaceAdjective($defender->race);
        $data['defender']['realm_adjective'] = $this->realmHelper->getAlignmentAdjective($defender->realm->alignment);

        # Look at invasion data of units defending, units lost, and units killed to get the full scope of slots used
        $data['defender']['units'] = [];

        foreach($invasion->data['defender']['units_defending'] as $slot => $amount)
        {
            if($amount > 0)
            {
                $unitName = $this->unitHelper->getUnitName($slot, $defender->race);
                $data['defender']['units'][$unitName]['defending'] = $amount;    
            }
        }

        foreach($invasion->data['defender']['units_lost'] as $slot => $amount)
        {
            if($amount > 0 or $invasion->data['defender']['units_defending'][$slot] > 0)
            {
                $unitName = $this->unitHelper->getUnitName($slot, $defender->race);
                $data['defender']['units'][$unitName]['lost'] = $amount;
            }
        }

        $data['winner'] = $invasion->data['result']['success'] ? 'attacker' : 'defender';

        # Win type is determined based on OP:DP ratio
        $opDpRatio = $invasion->data['result']['op_dp_ratio'] < 1 ? 1 / $invasion->data['result']['op_dp_ratio'] : $invasion->data['result']['op_dp_ratio'];

        # Start by getting the opDpRatio to be 
        if ($opDpRatio < 1.05)
        {
            $data['win_type'] = 'narrowly';
        }
        elseif ($opDpRatio > 1.05 && $opDpRatio < 1.1)
        {
            $data['win_type'] = 'barely';
        }
        elseif ($opDpRatio > 1.1)
        {
            $data['win_type'] = 'easily';
        }

        return $data;
    }

}