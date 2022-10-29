<?php

namespace OpenDominion\Services\Dominion;

use DateTime;
use LogicException;
use OpenDominion\Models\Dominion;

class HistoryService
{
    public const EVENT_TICK = 'tick';
    public const EVENT_ACTION_DAILY_BONUS = 'daily bonus';
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

    public const EVENT_WATCH_DOMINION = 'watched dominion';
    public const EVENT_UNWATCH_DOMINION = 'unwatched dominion';

    public const EVENT_RESEARCH_BEGIN = 'began research';
    public const EVENT_RESEARCH_COMPLETE = 'completed research';


    /**
     * Returns a cloned dominion instance with state at a certain time.
     *
     * @param Dominion $dominion
     * @param DateTime $at
     * @return Dominion
     */
    public function getDominionStateAtTime(Dominion $dominion, DateTime $at): Dominion
    {
        $clone = clone $dominion;

        // todo: add support for future state
        // if $at < now(), vvv
        // elseif $at > now(), where created_at <= $at && $clone->$attribute += $deltaValue;

        $history = $dominion->history()
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
     * Records history changes in delta of a dominion.
     *
     * @param Dominion $dominion
     * @param array $deltaAttributes
     * @param string $event
     */
    public function record(Dominion $dominion, array $deltaAttributes, string $event)
    {
        if (empty($deltaAttributes)) {
            return;
        }

        $tick = $dominion->round->ticks;

        $dominion->history()->create([
            'event' => $event,
            'delta' => $deltaAttributes,
            'tick' => $tick,
        ]);
    }

    /**
     * Returns the attribute delta of a changed dominion.
     *
     * @param Dominion $dominion
     * @return array
     */
    public function getDeltaAttributes(Dominion $dominion): array
    {
        $attributeKeys = $this->getChangedAttributeKeys($dominion);

        // someone handy with array functions pls optimize/refactor
        $oldAttributes = collect($dominion->getOriginal())
            ->intersectByKeys(array_flip($attributeKeys));

        $newAttributes = collect($dominion->getAttributes())
            ->intersectByKeys(array_flip($attributeKeys));

        return $newAttributes->map(function ($value, $key) use ($dominion, $oldAttributes)
        {
            $attributeType = gettype($dominion->getAttribute($key));

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
                  return 1;
                  break;

                default:
                    throw new LogicException("Unable to typecast attribute {$key} to type {$attributeType}");
            }
        })->toArray();
    }

    /**
     * Returns the changed attribute keys of a dominion.
     *
     * @param Dominion $dominion
     * @return array
     */
    protected function getChangedAttributeKeys(Dominion $dominion): array
    {
        return collect($dominion->getAttributes())
            ->diffAssoc(collect($dominion->getOriginal()))
            ->except([
                'id',
                'user_id',
                'pack_id',
                'round_id',
                'realm_id',
                'race_id',
                'title_id',
                'name',
                'ruler_name',
                'peasants_last_hour',
                'created_at',
                'updated_at',
                'daily_gold',
                'daily_land',
                'council_last_read',
                'news_last_read',
                'royal_guard_active_at',
                'elite_guard_active_at',
                'barbarian_guard_active_at',
                'last_tick_at',
                'monarchy_vote_for_dominion_id',
                'tick_voted',
                'most_recent_improvement_resource',
                'most_theft_improvement_resource',
                'most_recent_exchange_from',
                'most_recent_exchange_to',
            ])->keys()->toArray();
    }
}
