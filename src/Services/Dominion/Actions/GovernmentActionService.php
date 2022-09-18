<?php

namespace OpenDominion\Services\Dominion\Actions;

use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Realm;
use OpenDominion\Services\Dominion\GovernmentService;
use OpenDominion\Services\NotificationService;
use OpenDominion\Services\Realm\HistoryService;
use OpenDominion\Traits\DominionGuardsTrait;
use RuntimeException;


use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Calculators\Dominion\GovernmentCalculator;

class GovernmentActionService
{
    use DominionGuardsTrait;

    /** @var GovernmentService */
    protected $governmentService;

    /** @var NotificationService */
    protected $notificationService;

    /** @var SpellCalculator */
    protected $spellCalculator;
    /**
     * GovernmentActionService constructor.
     *
     * @param GovernmentService $governmentService
     */
    public function __construct(
        GovernmentService $governmentService,
        GovernmentCalculator $governmentCalculator,
        NotificationService $notificationService,
        SpellCalculator $spellCalculator
        )
    {
        $this->governmentService = $governmentService;
        $this->governmentCalculator = $governmentCalculator;
        $this->notificationService = $notificationService;
        $this->spellCalculator = $spellCalculator;
    }

    /**
     * Casts a Dominion's vote for monarch.
     *
     * @param Dominion $dominion
     * @param int $monarch_id
     * @throws RuntimeException
     */
    public function voteForMonarch(Dominion $dominion, ?int $monarch_id)
    {
        $this->guardLockedDominion($dominion);
        $this->guardActionsDuringTick($dominion);

        // Qur: Statis
        if($dominion->getSpellPerkValue('stasis'))
        {
            throw new GameException('You cannot take government actions while you are in stasis.');
        }

        if($dominion->round->mode == 'deathmatch' or $dominion->round->mode == 'deathmatch-duration')
        {
            throw new GameException('You cannot vote for governor in deathmatches.');
        }

        if(!$this->governmentCalculator->canVote($dominion))
        {
            throw new GameException('You cannot vote.');
        }

        $monarch = Dominion::find($monarch_id);
        if ($monarch == null)
        {
            throw new RuntimeException('Dominion not found.');
        }
        if ($dominion->realm != $monarch->realm)
        {
            throw new RuntimeException('You cannot vote for a Governor outside of your realm.');
        }
        if ($monarch->is_locked)
        {
            throw new RuntimeException('You cannot vote for a locked dominion to be your Governor.');
        }
        if(request()->getHost() == 'sim.odarena.com')
        {
            throw new GameException('Voting is disabled in the sim.');
        }
        if($dominion->race->getPerkValue('cannot_vote'))
        {
            throw new GameException($dominion->race->name . ' cannot vote for Governor.');
        }
        if($monarch->race->getPerkValue('cannot_vote'))
        {
            throw new GameException($monarch->race->name . ' cannot be Governor.');
        }

        // Qur: Statis
        if($this->spellCalculator->getPassiveSpellPerkValue($monarch, 'stasis'))
        {
            throw new GameException($monarch->name . ' is in stasis and cannot be voted for Governor.');
        }

        $dominion->monarchy_vote_for_dominion_id = $monarch->id;
        $dominion->tick_voted = $dominion->round->ticks;
        $dominion->save();

        $this->governmentService->checkMonarchVotes($dominion->realm);
    }

    /**
     * Changes a Dominion's realm name.
     *
     * @param Dominion $dominion
     * @param string $name
     * @throws GameException
     */
    public function updateRealm(Dominion $dominion, ?string $motd, ?string $name, ?int $contribution, ?string $discordLink)
    {
        $this->guardLockedDominion($dominion);
        $this->guardActionsDuringTick($dominion);

        // Qur: Statis
        if($dominion->getSpellPerkValue('stasis'))
        {
            throw new GameException('You cannot take government actions while you are in stasis.');
        }

        if (!$dominion->isMonarch()) {
            throw new GameException('Only the Governor can make changes to their realm.');
        }

        if ($motd && strlen($motd) > 400) {
            throw new GameException('Realm messages are limited to 400 characters.');
        }

        if ($name && strlen($name) > 100) {
            throw new GameException('Realm names are limited to 100 characters.');
        }

        if (isset($contribution) and ($contribution < 0 or $contribution > 10))
        {
            throw new GameException('Contribution must be a value between 0 and 10.');
        }

        if ($discordLink)
        {
            if(
                !filter_var($discordLink, FILTER_VALIDATE_URL) or
                (strlen($discordLink) >= strlen('https://discord.gg/xxxxxxx') and strlen($discordLink) <= strlen('https://discord.gg/xxxxxx')) or
                substr($discordLink,0,19) !== 'https://discord.gg/' or
                $discordLink == 'https://discord.gg/xxxxxxx'
              )
            {
                throw new GameException('"' . $discordLink . '" is not a valid Discord link. It should be in the format of https://discord.gg/xxxxxxx');
            }

            if($discordLink == config('app.discord_invite_link'))
            {
                throw new GameException('You cannot use ' . config('app.discord_invite_link') . ' because it is the ODARENA Discord link. Please insert your Realm\'s own Discord link here.');
            }

        }

        if ($motd)
        {
            $dominion->realm->motd = $motd;
            $dominion->realm->motd_updated_at = now();
        }
        if ($name)
        {
            $dominion->realm->name = $name;
        }
        if ($discordLink)
        {
            $dominion->realm->discord_link = $discordLink;
        }

        if (isset($contribution))
        {
            $dominion->realm->contribution = $contribution;
        }

        $dominion->realm->save(['event' => HistoryService::EVENT_ACTION_REALM_UPDATED]);
    }

}
