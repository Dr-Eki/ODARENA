<?php

namespace OpenDominion\Services\Dominion\Actions;

use DB;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\AllianceOffer;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Protectorship;
use OpenDominion\Models\ProtectorshipOffer;
use OpenDominion\Models\Realm;
use OpenDominion\Models\RealmAlliance;
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

    public function submitProtectorshipOffer(Dominion $protector, Dominion $protected)
    {
        $this->guardLockedDominion($protector);
        $this->guardActionsDuringTick($protector);
        $this->guardLockedDominion($protected);
        $this->guardActionsDuringTick($protected);

        DB::transaction(function () use ($protector, $protected)
        {
            if(!$protector or !$protected)
            {
                throw new GameException('Invalid protectorship offer.');
            }

            if($protector->isProtector())
            {
                throw new GameException('You are already protecting a dominion.');
            }

            if($protected->hasProtector())
            {
                throw new GameException($protected->name . ' already has a protector.');
            }

            if($protected->realm->id !== $protector->realm->id)
            {
                throw new GameException('You cannot offer protection to a dominion outside of your realm.');
            }

            if($protected->round->id !== $protector->round->id)
            {
                throw new GameException('You cannot offer protection to a dominion outside of the current round.');
            }

            if(!$this->governmentCalculator->canOfferProtectorship($protector))
            {
                throw new GameException('You cannot offer protection.');
            }

            if(!$this->governmentCalculator->canBeProtected($protected))
            {
                throw new GameException($protected->name . ' cannot be protected.');
            }

            $protectorshipOffer = ProtectorshipOffer::create([
                'protector_id' => $protector->id,
                'protected_id' => $protected->id,
                'status' => 0
            ]);

            if($protectorshipOffer)
            {
                GameEvent::create([
                    'round_id' => $protector->round_id,
                    'source_type' => Dominion::class,
                    'source_id' => $protector->id,
                    'target_type' => Dominion::class,
                    'target_id' => $protected->id,
                    'type' => 'protectorship_offered',
                    'data' => NULL,
                    'tick' => $protector->round->ticks
                ]);

                $this->notificationService
                ->queueNotification('received_protectorship_offer', [
                    'protectorDominionId' => $protector->id,
                ])
                ->sendNotifications($protected, 'irregular_dominion');
            }
        });
    }

    public function rescindProtectorshipOffer(ProtectorshipOffer $protectorshipOffer, Dominion $responder)
    {
        $protector = $protectorshipOffer->protector;
        $protected = $protectorshipOffer->protected;

        $this->guardLockedDominion($protector);
        $this->guardActionsDuringTick($protector);

        $this->guardLockedDominion($protected);
        $this->guardActionsDuringTick($protected);

        $this->guardLockedDominion($responder);
        $this->guardActionsDuringTick($responder);

        DB::transaction(function () use ($protector, $responder, $protectorshipOffer)
        {
            if($protector->id !== $responder->id)
            {
                throw new GameException('Invalid protectorship offer.');
            }

            if(!$this->governmentCalculator->canRescindProtectorshipOffer($protector, $protectorshipOffer))
            {
                throw new GameException('You cannot rescind this protectorship offer.');
            }

            $protectorshipOffer->delete();

        });
    }

    public function answerProtectorshipOffer(ProtectorshipOffer $protectorshipOffer, string $answer, Dominion $responder)
    {
        $protector = $protectorshipOffer->protector;
        $protected = $protectorshipOffer->protected;

        $this->guardLockedDominion($responder);
        $this->guardActionsDuringTick($responder);

        $this->guardLockedDominion($protector);
        $this->guardActionsDuringTick($protector);

        $this->guardLockedDominion($protected);
        $this->guardActionsDuringTick($protected);

        DB::transaction(function () use ($protectorshipOffer, $protector, $protected, $answer, $responder)
        {
            if(!$protector or !$protected)
            {
                throw new GameException('Invalid protectorship offer.');
            }

            if($responder->id !== $protected->id)
            {
                throw new GameException('You cannot answer this offer.');
            }

            if($protector->isProtector())
            {
                throw new GameException($protector->name . ' is already protecting someone else.');
            }

            if($protected->hasProtector())
            {
                throw new GameException('You already have a protector.');
            }

            if($protected->realm->id !== $protector->realm->id)
            {
                throw new GameException('You cannot enter into protectorship with a dominion outside of your realm.');
            }

            if($protected->round->id !== $protector->round->id)
            {
                throw new GameException('You cannot enter into protectorship with a dominion outside of the current round.');
            }

            if(!$this->governmentCalculator->canBeProtector($protector) and $answer == 'accept')
            {
                throw new GameException($protector->name . ' cannot be a protector.');
            }

            if(!$this->governmentCalculator->canBeProtected($protected))
            {
                throw new GameException('You cannot be protected.');
            }

            if($answer == 'accept')
            {
                $protectorship = Protectorship::create([
                    'protector_id' => $protector->id,
                    'protected_id' => $protected->id,
                    'tick' => $protected->round->ticks
                ]);
    
                if($protectorship)
                {
                    GameEvent::create([
                        'round_id' => $protector->round_id,
                        'source_type' => Dominion::class,
                        'source_id' => $protector->id,
                        'target_type' => Dominion::class,
                        'target_id' => $protected->id,
                        'type' => 'protectorship_accepted',
                        'data' => NULL,
                        'tick' => $protector->round->ticks
                    ]);

                    $this->notificationService
                    ->queueNotification('received_protectorship_offer_accepted', [
                        'protectedDominionId' => $protected->id,
                    ])
                    ->sendNotifications($protector, 'irregular_dominion');

                    # Delete all other protectorship offers
                    ProtectorshipOffer::where([
                        'protected_id' => $protected->id,
                    ])->delete();

                    ProtectorshipOffer::where([
                        'protector_id' => $protector->id,
                    ])->delete();
                }
            }
            elseif($answer == 'decline')
            {
                # Delete the protectorship offer
                $protectorshipOffer->delete();

                GameEvent::create([
                    'round_id' => $protector->round_id,
                    'source_type' => Dominion::class,
                    'source_id' => $protector->id,
                    'target_type' => Dominion::class,
                    'target_id' => $protected->id,
                    'type' => 'protectorship_declined',
                    'data' => NULL,
                    'tick' => $protector->round->ticks
                ]);

                $this->notificationService
                ->queueNotification('received_protectorship_offer_declined', [
                    'protectedDominionId' => $protector->id,
                ])
                ->sendNotifications($protector, 'irregular_dominion');
            }
            else
            {
                throw new GameException('Invalid answer.');
            }

        });
    }

    public function submitAllianceOffer(Dominion $governor, Realm $inviter, Realm $invited)
    {
        $this->guardLockedDominion($governor);
        $this->guardActionsDuringTick($governor);

        DB::transaction(function () use ($governor, $inviter, $invited)
        {

            if(!in_array($governor->round->mode, ['factions', 'factions-duration']))
            {
                throw new GameException('You cannot form alliances in this round.');
            }

            if(!$this->governmentService->hasMonarch($inviter))
            {
                throw new GameException('Realm #' . $inviter->number . ' does not have a governor.');
            }

            if(!$this->governmentService->hasMonarch($invited))
            {
                throw new GameException('Realm #' . $invited->number . ' does not have a governor.');
            }

            if(!$this->governmentService->hasMonarch($invited))
            {
                throw new GameException('Realm #' . $invited->number . ' does not have a governor.');
            }

            if(!$governor or !$inviter or !$invited)
            {
                throw new GameException('Invalid protectorship offer.');
            }

            if(!$governor->isMonarch())
            {
                throw new GameException('You must be realm governor to submit alliance offer.');
            }

            if($inviter->isAlly($invited))
            {
                throw new GameException('Realm #' . $invited->number . ' is already an ally.');
            }

            if($inviter->realm->id == $invited->realm->id)
            {
                throw new GameException('You cannot form an alliance with yourself.');
            }

            if($invited->realm->alignment == 'npc')
            {
                throw new GameException('You cannot form an alliance with the Barbarians.');
            }

            if($inviter->round->id !== $invited->round->id)
            {
                throw new GameException('You cannot offer protection to a dominion outside of the current round.');
            }

            if(!$this->governmentCalculator->canOfferAlliance($inviter, $invited))
            {
                throw new GameException('You cannot offer protection.');
            }

            $invitedGovernor = $this->governmentService->getRealmMonarch($invited);

            if($invitedGovernor->realm->id !== $invited->id or !$invitedGovernor->isMonarch() or !$invitedGovernor)
            {
                throw new GameException('Invalid governor of invited realm. Try again?');
            }

            $allianceOffer = AllianceOffer::create([
                'inviter_realm_id' => $inviter->id,
                'invited_realm_id' => $invited->id,
                'status' => 0
            ]);

            if($allianceOffer)
            {
                GameEvent::create([
                    'round_id' => $governor->round_id,
                    'source_type' => Realm::class,
                    'source_id' => $inviter->id,
                    'target_type' => Realm::class,
                    'target_id' => $invited->id,
                    'type' => 'alliance_offered',
                    'data' => NULL,
                    'tick' => $governor->round->ticks
                ]);

                $this->notificationService
                ->queueNotification('received_alliance_offer', [
                    'allyRealmGovernorId' => $governor->id,
                    'allyRealmId' => $inviter->id,
                ])
                ->sendNotifications($invitedGovernor, 'irregular_dominion');
            }
        });
    }

    public function rescindAllianceOffer(AllianceOffer $allianceOffer, Dominion $responder)
    {
        DB::transaction(function () use ($allianceOffer, $responder)
        {
            if(!$responder->isMonarch())
            {
                throw new GameException('You must be realm governor to rescind an alliance offer.');
            }

            if(!$this->governmentCalculator->canRescindAllianceOffer($allianceOffer))
            {
                throw new GameException('You cannot rescind this alliance offer.');
            }

            $allianceOffer->delete();
        });
    }

    public function answerAllianceOffer(AllianceOffer $allianceOffer, string $answer, Dominion $responder)
    {

        $this->guardLockedDominion($responder);
        $this->guardActionsDuringTick($responder);

        DB::transaction(function () use ($allianceOffer, $answer, $responder)
        {

            $inviter = $allianceOffer->inviter;
            $invited = $allianceOffer->invited;

            if(!$responder->isMonarch())
            {
                throw new GameException('You must be realm governor to answer alliance offer.');
            }

            if(!$inviter->hasMonarch())
            {
                throw new GameException('Realm #' . $inviter->number . ' does not have a governor. You cannot accept or decline this offer at the moment.');
            }

            if($invited->isAlly($inviter))
            {
                throw new GameException('Realm #' . $inviter->number . ' is already an ally.');
            }

            if($responder->realm->id !== $invited->id)
            {
                throw new GameException('You cannot answer this offer.');
            }

            if($invited->id == $inviter->id)
            {
                throw new GameException('You cannot enter into an alliance with your realm.');
            }

            if($invited->round->id !== $inviter->round->id)
            {
                throw new GameException('You cannot enter into an alliance with a realm outside of the current round.');
            }

            $inviterGovernor = $this->governmentService->getRealmMonarch($inviter);

            if($answer == 'accept')
            {
                $alliance = RealmAlliance::create([
                    'realm_id' => $inviter->id,
                    'ally_id' => $invited->id,
                    'established_tick' => $responder->round->ticks
                ]);
    
                if($alliance)
                {
                    GameEvent::create([
                        'round_id' => $responder->round_id,
                        'source_type' => Realm::class,
                        'source_id' => $inviter->id,
                        'target_type' => Realm::class,
                        'target_id' => $invited->id,
                        'type' => 'alliance_accepted',
                        'data' => NULL,
                        'tick' => $alliance->established_tick
                    ]);

                    $this->notificationService
                    ->queueNotification('received_alliance_offer_accepted', [
                        'invitedRealmId' => $invited->id,
                    ])
                    ->sendNotifications($inviterGovernor, 'irregular_dominion');

                    # Delete alliance offer
                    $allianceOffer->delete();
                }
            }
            elseif($answer == 'decline')
            {
                
                # Delete the protectorship offer
                $allianceOffer->delete();

                GameEvent::create([
                    'round_id' => $responder->round_id,
                    'source_type' => Dominion::class,
                    'source_id' => $inviter->id,
                    'target_type' => Dominion::class,
                    'target_id' => $invited->id,
                    'type' => 'protectorship_declined',
                    'data' => NULL,
                    'tick' => $responder->round->ticks
                ]);

                $this->notificationService
                ->queueNotification('received_alliance_offer_declined', [
                    'invitedRealmId' => $invited->id,
                ])
                ->sendNotifications($inviterGovernor, 'irregular_dominion');
            }
            else
            {
                throw new GameException('Invalid answer.');
            }

        });
    }

}
