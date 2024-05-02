<?php

namespace OpenDominion\Services\Hold;

use DateTime;
use LogicException;
use OpenDominion\Models\Hold;

class HistoryService
{
    public const EVENT_TICK = 'tick';
    public const EVENT_ACTION_DAILY_BONUS = 'daily bonus';
    public const EVENT_ACTION_DESECRATION = 'desecration';
    public const EVENT_ACTION_EXPLORE = 'explore';
    public const EVENT_ACTION_CONSTRUCT = 'construct';
    public const EVENT_ACTION_DESTROY = 'destroy';
    public const EVENT_ACTION_REZONE = 'rezone';
    public const EVENT_ACTION_IMPROVE = 'improve';
    public const EVENT_ACTION_BANK = 'bank';
    public const EVENT_ACTION_TECH = 'tech';
    public const EVENT_ACTION_CHANGE_DRAFT_RATE = 'change draft rate';
    public const EVENT_ACTION_TRAIN = 'train';
    public const EVENT_ACTION_RELEASE = 'release';
    public const EVENT_ACTION_CAST_SPELL = 'cast spell';
    public const EVENT_ACTION_BREAK_SPELL = 'break spell';
    public const EVENT_ACTION_SORCERY = 'cast sorcery';
    public const EVENT_ACTION_PERFORM_ESPIONAGE_OPERATION = 'perform espionage operation';
    public const EVENT_ACTION_INVADE = 'invade';
    public const EVENT_ACTION_INVADE_SUPPORT = 'invasion support';
    public const EVENT_ACTION_ATTACK_ARTEFACT = 'artefact attack';
    public const EVENT_ACTION_EXPEDITION = 'expedition';
    public const EVENT_ACTION_THEFT = 'theft';
    public const EVENT_ACTION_SABOTAGE = 'sabotage';
    public const EVENT_ACTION_JOIN_ROYAL_GUARD = 'join peacekeepers league';
    public const EVENT_ACTION_JOIN_ELITE_GUARD = 'join warriors league';
    public const EVENT_ACTION_LEAVE_ROYAL_GUARD = 'leave peacekeepers league';
    public const EVENT_ACTION_LEAVE_ELITE_GUARD = 'leave warriors league';
    public const EVENT_ROUND_VICTORY = 'round victory';
    public const EVENT_ROUND_COUNTDOWN = 'round countdown';

    public const EVENT_ACTION_NOTE = 'update notes';
    public const EVENT_ACTION_SEND_UNITS = 'units sent';

    public const EVENT_SUBMIT_TO_DEITY_BEGUN = 'deity submission';
    public const EVENT_SUBMIT_TO_DEITY_COMPLETED = 'deity submission completed';
    public const EVENT_RENOUNCE_DEITY = 'renounce deity';

    public const EVENT_ISSUE_DECREE = 'decree issued';
    public const EVENT_REVOKE_DECREE = 'decree revoked';

    public const EVENT_WATCH_DOMINION = 'watched hold';
    public const EVENT_UNWATCH_DOMINION = 'unwatched hold';

    public const EVENT_RESEARCH_BEGIN = 'began research';
    public const EVENT_RESEARCH_COMPLETE = 'completed research';


    /**
     * Returns a cloned hold instance with state at a certain time.
     *
     * @param Hold $hold
     * @param DateTime $at
     * @return Hold
     */
    public function getHoldStateAtTime(Hold $hold, DateTime $at): Hold
    {
        $clone = clone $hold;

        // todo: add support for future state
        // if $at < now(), vvv
        // elseif $at > now(), where created_at <= $at && $clone->$attribute += $deltaValue;

        $history = $hold->history()
            ->where('created_at', '>', $at)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($history->isEmpty()) {
            return $clone;
        }

        $history->each(function ($item, $key) use ($clone) {
            foreach ($item->delta as $attribute => $deltaValue) {
                $type = gettype($deltaValue);

                if ($type === 'bool') {
                    $clone->$attribute = !$deltaValue;
                } else {
                    $clone->$attribute -= $deltaValue;
                }
            }
        });

        return $clone;
    }

    /**
     * Records history changes in delta of a hold.
     *
     * @param Hold $hold
     * @param array $deltaAttributes
     * @param string $event
     */
    public function record(Hold $hold, array $deltaAttributes, string $event)
    {
        if (empty($deltaAttributes)) {
            return;
        }

        $tick = $hold->round->ticks;

        $hold->history()->create([
            'event' => $event,
            'delta' => $deltaAttributes,
            'tick' => $tick,
        ]);
    }

    /**
     * Returns the attribute delta of a changed hold.
     *
     * @param Hold $hold
     * @return array
     */
    public function getDeltaAttributes(Hold $hold): array
    {
        $attributeKeys = $this->getChangedAttributeKeys($hold);

        // someone handy with array functions pls optimize/refactor
        $oldAttributes = collect($hold->getOriginal())
            ->intersectByKeys(array_flip($attributeKeys));

        $newAttributes = collect($hold->getAttributes())
            ->intersectByKeys(array_flip($attributeKeys));

        return $newAttributes->map(function ($value, $key) use ($hold, $oldAttributes)
        {
            $attributeType = gettype($hold->getAttribute($key));

            switch ($attributeType)
            {
                case 'boolean':
                    return (bool)$value;
                    break;

                case 'float':
                case 'double':
                    return ((float)$value - (float)$oldAttributes->get($key));
                    break;

                case 'integer':
                    return ((int)$value - (int)$oldAttributes->get($key));
                    break;

                case 'string':
                  return (string)$value;
                  break;

                case 'json':
                    return json_decode($value, true);
                    break;

                case 'array':
                    return $value;
                    break;

                default:
                    throw new LogicException("Unable to typecast attribute {$key} to type {$attributeType}");
            }
        })->toArray();
    }

    /**
     * Returns the changed attribute keys of a hold.
     *
     * @param Hold $hold
     * @return array
     */
    protected function getChangedAttributeKeys(Hold $hold): array
    {

        return [];

        return collect($hold->getAttributes())
            ->diffAssoc(collect($hold->getOriginal()))
            ->except([
                'id',
                'round_id',
                'race_id',
                'title_id',
                'name',
                'ruler_name',
                'peasants_last_hour',
                'created_at',
                'updated_at',
                'sold_resources',
                'desired_resources',
            ])->keys()->toArray();
    }
}
