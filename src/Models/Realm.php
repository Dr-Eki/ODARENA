<?php

namespace OpenDominion\Models;
use Illuminate\Database\Eloquent\Collection;

use OpenDominion\Services\Realm\HistoryService;

/**
 * OpenDominion\Models\Realm
 *
 * @property int $id
 * @property int $round_id
 * @property int|null $monarch_dominion_id
 * @property string $alignment
 * @property int $number
 * @property string|null $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\Council\Thread[] $councilThreads
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\Dominion[] $dominions
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\Dominion[] $infoOpTargetDominions
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\InfoOp[] $infoOps
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\Pack[] $packs
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\Realm\History[] $history
 * @property-read \OpenDominion\Models\Round $round
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Realm newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Realm newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\Realm query()
 * @mixin \Eloquent
 */
class Realm extends AbstractModel
{
    public function councilThreads()
    {
        return $this->hasMany(Council\Thread::class);
    }

    public function dominions()
    {
        return $this->hasMany(Dominion::class);
    }

    public function history()
    {
        return $this->hasMany(Realm\History::class);
    }

    public function monarch()
    {
        return $this->hasOne(Dominion::class, 'id', 'monarch_dominion_id');
    }

    public function packs()
    {
        return $this->hasMany(Pack::class);
    }

    public function round()
    {
        return $this->belongsTo(Round::class);
    }

    public function pack()
    {
        return $this->hasOne(Pack::class);
    }
  
    public function resources()
    {
        return $this->belongsToMany(
            Resource::class,
            'realm_resources',
            'realm_id',
            'resource_id'
        )
            ->withPivot('amount');
    }
    
    # Allies are realms with which this dominion has a RealmAlliance or other realms that have a RealmAlliance with this realm
    public function getAllies()
    {
        $realmIds = [];

        foreach(RealmAlliance::where('realm_id', $this->id)->orWhere('allied_realm_id', $this->id)->pluck('realm_id', 'allied_realm_id')->toArray() as $key => $value)
        {
            $key !== $this->id ? $realmIds[] = $key : null;
            $value !== $this->id ? $realmIds[] = $value : null;

        }
    
        return self::whereIn('id', $realmIds)->get();
    }

    public function isAlly(Realm $realm)
    {
        return $this->getAllies()->contains($realm);
    }

    public function allianceOffers()
    {
        # Check if this realm has sent an alliance offer to another realm or has received an alliance offer from another realm
        return RealmAlliance::where('realm_id', $this->id)->orWhere('allied_realm_id', $this->id)->get();
    }

    public function realmArtefacts()
    {
        return $this->hasMany(RealmArtefact::class, 'realm_id');
    }

    public function artefacts()
    {
        return $this->hasManyThrough(
            Artefact::class,
            RealmArtefact::class,
            'realm_id',
            'id',
            'id',
            'artefact_id'
        );
    }

    public function hasArtefacts()
    {
        return $this->artefacts()->count();
    }

    public function hasMonarch(): bool
    {
        return ($this->monarch !== null);
    }
    public function getPackLeader()
    {
        return $this->pack->leader;
    }
    
    # Artefacts stuff
    protected function getArtefactPerks()
    {
        return $this->artefacts->filter(
            function ($artefact) {
                return $artefact->power > 0;
            }
        )->flatMap(
            function ($artefact) {
                return $artefact->perks;
            }
        );
    }

    /**
     * @param string $key
     * @return float
     */
    public function getArtefactPerkValue(string $key): float
    {
        $perks = $this->getArtefactPerks()->groupBy('key');
        if (isset($perks[$key])) {
            return (float)$perks[$key]->sum('pivot.value');
        }
        return 0;
    }

    /**
     * @param string $key
     * @return float
     */
    public function getArtefactPerkMultiplier(string $key): float
    {
        return ($this->getArtefactPerkValue($key) / 100);
    }

    // todo: move to eloquent events, see $dispatchesEvents
    public function save(array $options = [])
    {
        $recordChanges = isset($options['event']);

        if ($recordChanges) {
            $realmHistoryService = app(HistoryService::class);
            $deltaAttributes = $realmHistoryService->getDeltaAttributes($this);
        }

        $saved = parent::save($options);

        if ($saved && $recordChanges) {
            /** @noinspection PhpUndefinedVariableInspection */
            $realmHistoryService->record($this, $deltaAttributes, $options['event']);
        }

        return $saved;
    }
}
