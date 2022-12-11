<?php

namespace OpenDominion\Services\Dominion;

use DB;
use Carbon\Carbon;
use OpenDominion\Models\Round;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Stat;
use OpenDominion\Models\DominionStat;
use OpenDominion\Models\RealmStat;
use OpenDominion\Models\RoundStat;

class StatsService
{

    # Dominion stats
    public function getStat(Dominion $dominion, string $statKey): int
    {
        if($stat = Stat::where('key', $statKey)->first())
        {
            if($dominionStat = DominionStat::where('stat_id',$stat->id)->where('dominion_id',$dominion->id)->first())
            {
                return $dominionStat->value;
            }
        }

        return 0;
    }

    public function updateStat(Dominion $dominion, string $statKey, int $value): void
    {
        $stat = Stat::where('key', $statKey)->first();

        if($this->hasStat($dominion, $statKey))
        {
            DB::transaction(function () use ($dominion, $stat, $value)
            {
                DominionStat::where('dominion_id', $dominion->id)->where('stat_id', $stat->id)
                ->increment('value', $value);
            });
        }
        else
        {
            DB::transaction(function () use ($dominion, $stat, $value)
            {
                DominionStat::create([
                    'dominion_id' => $dominion->id,
                    'stat_id' => $stat->id,
                    'value' => $value
                ]);
            });

        }
    }

    public function setStat(Dominion $dominion, string $statKey, int $value): void
    {
        $stat = Stat::where('key', $statKey)->first();

        if($this->hasStat($dominion, $statKey))
        {
            DB::transaction(function () use ($dominion, $stat, $value)
            {
                DominionStat::where('dominion_id', $dominion->id)->where('stat_id', $stat->id)
                ->update(['value' => $value]);
            });
        }
        else
        {
            DB::transaction(function () use ($dominion, $stat, $value)
            {
                DominionStat::create([
                    'dominion_id' => $dominion->id,
                    'stat_id' => $stat->id,
                    'value' => $value
                ]);
            });

        }
    }

    public function updateStats(Dominion $dominon, array $statValues): void
    {
        foreach($statValues as $stat => $value)
        {
            $this->updateStat($dominion, $stat, $value);
        }
    }

    public function setStats(Dominion $dominon, array $statValues): void
    {
        foreach($statValues as $stat => $value)
        {
            $this->setStat($dominion, $stat, $value);
        }
    }

    public function hasStat(Dominion $dominion, string $statKey): bool
    {
        $stat = Stat::where('key', $statKey)->first();

        if(!isset($stat->id))
        {
            dd('Stat key not found: '.$statKey.'');
        }

        return DominionStat::where('stat_id',$stat->id)->where('dominion_id',$dominion->id)->count() ? true : false;
    }

    # Realm stats
    public function getRealmStat(Realm $realm, string $statKey): int
    {
        if($stat = Stat::where('key', $statKey)->first())
        {
            if($realmStat = RealmStat::where('stat_id',$stat->id)->where('realm_id',$realm->id)->first())
            {
                return $realmStat->value;
            }
        }

        return 0;
    }

    public function updateRealmStat(Realm $realm, string $statKey, int $value): void
    {
        $stat = Stat::where('key', $statKey)->first();

        if($this->realmHasStat($realm, $statKey))
        {
            DB::transaction(function () use ($realm, $stat, $value)
            {
                RealmStat::where('realm_id', $realm->id)->where('stat_id', $stat->id)
                ->increment('value', $value);
            });
        }
        else
        {
            DB::transaction(function () use ($realm, $stat, $value)
            {
                RealmStat::create([
                    'realm_id' => $realm->id,
                    'stat_id' => $stat->id,
                    'value' => $value
                ]);
            });

        }
    }

    public function setRealmStat(Realm $realm, string $statKey, int $value): void
    {
        $stat = Stat::where('key', $statKey)->first();

        if($this->realmHasStat($realm, $statKey))
        {
            DB::transaction(function () use ($realm, $stat, $value)
            {
                RealmStat::where('realm_id', $realm->id)->where('stat_id', $stat->id)
                ->update(['value' => $value]);
            });
        }
        else
        {
            DB::transaction(function () use ($realm, $stat, $value)
            {
                RealmStat::create([
                    'realm_id' => $realm->id,
                    'stat_id' => $stat->id,
                    'value' => $value
                ]);
            });

        }
    }

    public function realmHasStat(Realm $realm, string $statKey): bool
    {
        $stat = Stat::where('key', $statKey)->first();
        if(!$stat)
        {
        #  dd($stat, $statKey);
        }
        return RealmStat::where('stat_id',$stat->id)->where('realm_id',$realm->id)->first() ? true : false;
    }



    # Round stats
    public function getRoundStat(Round $round, string $statKey): int
    {
        if($stat = Stat::where('key', $statKey)->first())
        {
            if($roundStat = RoundStat::where('stat_id',$stat->id)->where('round_id',$round->id)->first())
            {
                return $roundStat->value;
            }
        }

        return 0;
    }

    public function updateRoundStat(Round $round, string $statKey, int $value): void
    {
        $stat = Stat::where('key', $statKey)->first();

        if($this->roundHasStat($round, $statKey))
        {
            DB::transaction(function () use ($round, $stat, $value)
            {
                RoundStat::where('round_id', $round->id)->where('stat_id', $stat->id)
                ->increment('value', $value);
            });
        }
        else
        {
            DB::transaction(function () use ($round, $stat, $value)
            {
                RoundStat::create([
                    'round_id' => $round->id,
                    'stat_id' => $stat->id,
                    'value' => $value
                ]);
            });

        }
    }

    public function setRoundStat(Round $round, string $statKey, int $value): void
    {
        $stat = Stat::where('key', $statKey)->first();

        if($this->roundHasStat($round, $statKey))
        {
            DB::transaction(function () use ($round, $stat, $value)
            {
                RoundStat::where('round_id', $round->id)->where('stat_id', $stat->id)
                ->update(['value' => $value]);
            });
        }
        else
        {
            DB::transaction(function () use ($round, $stat, $value)
            {
                RoundStat::create([
                    'round_id' => $round->id,
                    'stat_id' => $stat->id,
                    'value' => $value
                ]);
            });

        }
    }

    public function roundHasStat(Round $round, string $statKey): bool
    {
        $stat = Stat::where('key', $statKey)->first();
        if(!$stat)
        {
        #  dd($stat, $statKey);
        }
        return RoundStat::where('stat_id',$stat->id)->where('round_id',$round->id)->first() ? true : false;
    }

}
