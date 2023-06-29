<?php

namespace OpenDominion\Helpers;

use Illuminate\Support\Collection;

use OpenDominion\Models\Artefact;
use OpenDominion\Models\DecreeState;
use OpenDominion\Models\Deity;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionDecreeState;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Realm;
use OpenDominion\Models\RealmArtefact;
use OpenDominion\Models\Spell;
use OpenDominion\Models\Spyop;
use OpenDominion\Models\Tech;

use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;

use OpenDominion\Helpers\DesecrationHelper;
use OpenDominion\Helpers\EventHelper;
use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Helpers\RealmHelper;
use OpenDominion\Helpers\RoundHelper;

class WorldNewsHelper
{
    protected $landCalculator;
    protected $militaryCalculator;
    protected $rangeCalculator;

    protected $desecrationHelper;
    protected $eventHelper;
    protected $raceHelper;
    protected $realmHelper;
    protected $roundHelper;

    public function __construct()
    {
        $this->landCalculator = app(LandCalculator::class);
        $this->militaryCalculator = app(MilitaryCalculator::class);
        $this->rangeCalculator = app(RangeCalculator::class);

        $this->desecrationHelper = app(DesecrationHelper::class);
        $this->eventHelper = app(EventHelper::class);
        $this->raceHelper = app(RaceHelper::class);
        $this->realmHelper = app(RealmHelper::class);
        $this->roundHelper = app(RoundHelper::class);
    }

    public function getWorldNewsString(Dominion $viewer, GameEvent $event): string
    {
        switch ($event->type)
        {
            case 'abandon_dominion':
                return $this->generateAbandonString($event->source, $event, $viewer);

            case 'alliance_accepted':
                return $this->generateAllianceAcceptedString($event->target, $event->source, $viewer);

            case 'alliance_broken':
                return $this->generateAllianceBrokenString($event->source, $event->target, $viewer);

            case 'alliance_declined':
                return $this->generateAllianceDeclinedString($event->target, $event->source, $viewer);

            case 'alliance_offered':
                return $this->generateAllianceOfferedString($event->target, $event->source, $viewer);

            case 'alliance_rescinded':
                return $this->generateAllianceOfferRescindedString($event->target, $event->source, $viewer);

            case 'artefact_completed':
                return $this->generateArtefactCompletedString($event->target, $event->source, $viewer);

            case 'barbarian_invasion':
                return $this->generateBarbarianInvasionString($event->source, $event, $viewer);

            case 'decree_issued':
                return $this->generateDecreeIssuedString($event->source, $event, $viewer);

            case 'decree_revoked':
                return $this->generateDecreeRevokedString($event->source, $event, $viewer);

            case 'deity_completed':
                return $this->generateDeityCompletedString($event->target, $event->source, $viewer);

            case 'deity_renounced':
                return $this->generateDeityRenouncedString($event->target, $event->source, $viewer);

            case 'desecration':
                return $this->generateDesecrationString($event->source, $event, $viewer);

            case 'expedition':
                return $this->generateExpeditionString($event->source, $event, $viewer);

            case 'governor':
                return $this->generateGovernorString($event->source, $event->target, $viewer);

            case 'invasion':
                return $this->generateInvasionString($event->source, $event->target, $event, $viewer);

            case 'invasion_support':
                return $this->generateInvasionSupportString($event->source, $event->target, $event, $viewer);

            case 'new_dominion':
                return $this->generateNewDominionString($event->source, $event->target, $viewer);

            case 'no_governor':
                return $this->generateNoGovernorString($viewer, $event->source);

            case 'protectorship_offered':
                return $this->generateProtectorshipOfferedString($event->target, $event->source, $viewer);

            case 'protectorship_accepted':
                return $this->generateProtectorshipAcceptedString($event->target, $event->source, $viewer);

            case 'protectorship_declined':
                return $this->generateProtectorshipDeclinedString($event->target, $event->source, $viewer);

            case 'round_countdown_duration':
            case 'round_countdown':
                return $this->generateCountdownString($event, $viewer);

            case 'research_completed':
                return $this->generateResearchCompletedString($event->target, $event->source, $viewer);

            case 'sabotage':
                return $this->generateSabotageString($event->source, $event->target, $event, $viewer);

            case 'sorcery':
                return $this->generateSorceryString($event->source, $event->target, $event, $viewer);

            case 'theft':
                return $this->generateTheftString($event->source, $event->target, $event, $viewer);

            default:
                return 'No string defined for event type <code>' . $event->type . '</code>.';
        }
    }
    
    public function generateAbandonString(Dominion $dominion, GameEvent $event, Dominion $viewer): string
    {
        /*
            Ants (# 2) was abandoned.
        */

        $string = sprintf(
            '%s was abandoned.',
            $this->generateDominionString($dominion, 'neutral', $viewer)
          );

        return $string;
    }

    public function generateAllianceOfferedString(Realm $invited, Realm $inviter, Dominion $viewer): string
    {
        $viewerIsInviterRealm = ($inviter->id == $viewer->realm->id);
        $viewerIsInvitedRealm = ($invited->id == $viewer->realm->id);

        if($viewerIsInviterRealm)
        {
            $string = sprintf(
                'We have invited %s to an alliance.',
                $this->generateRealmOnlyString($invited, 'neutral', $viewer)
              );
        }
        elseif($viewerIsInvitedRealm)
        {
            $string = sprintf(
                '%s has invited our realm to an alliance.',
                $this->generateRealmOnlyString($inviter, 'neutral', $viewer)
              );
        }
        else
        {
            $string = sprintf(
                'An alliance offer has been made between two foreign realms.'
              );
        }

        return $string;
    }

    public function generateAllianceAcceptedString(Realm $invited, Realm $inviter, Dominion $viewer): string
    {
        $viewerIsInviterRealm = ($inviter->id == $viewer->realm->id);
        $viewerIsInvitedRealm = ($invited->id == $viewer->realm->id);

        if($viewerIsInviterRealm)
        {
            $string = sprintf(
                'We are now allied with %s',
                $this->generateRealmOnlyString($invited, 'neutral', $viewer)
              );
        }
        elseif($viewerIsInvitedRealm)
        {
            $string = sprintf(
                'We are now allied with %s.',
                $this->generateRealmOnlyString($inviter, 'neutral', $viewer)
              );
        }
        else
        {
            $string = sprintf(
                '%s and %s have formed an alliance.',
                $this->generateRealmOnlyString($inviter, 'neutral', $viewer),
                $this->generateRealmOnlyString($invited, 'neutral', $viewer)
              );
        }
        
        return $string;
    }

    public function generateAllianceBrokenString(Realm $breaker, Realm $breakee, Dominion $viewer): string
    {
        $viewerIsBreakerRealm = ($breaker->id == $viewer->realm->id);
        $viewerIsBreakeeRealm = ($breakee->id == $viewer->realm->id);

        if($viewerIsBreakerRealm)
        {
            $string = sprintf(
                'We have broken our alliance with %s.',
                $this->generateRealmOnlyString($breakee, 'neutral', $viewer)
              );
        }
        elseif($viewerIsBreakeeRealm)
        {
            $string = sprintf(
                '%s has broken our alliance.',
                $this->generateRealmOnlyString($breaker, 'neutral', $viewer)
              );
        }
        else
        {
            $string = sprintf(
                '%s and %s have broken their alliance.',
                $this->generateRealmOnlyString($breaker, 'neutral', $viewer),
                $this->generateRealmOnlyString($breakee, 'neutral', $viewer)
              );
        }
        
        return $string;
    }

    public function generateAllianceDeclinedString(Realm $invited, Realm $inviter, Dominion $viewer): string
    {
        $viewerIsInviterRealm = ($inviter->id == $viewer->realm->id);
        $viewerIsInvitedRealm = ($invited->id == $viewer->realm->id);

        if($viewerIsInviterRealm)
        {
            $string = sprintf(
                '%s has declined our alliance invitation.',
                $this->generateRealmOnlyString($invited, 'neutral', $viewer)
              );
        }
        elseif($viewerIsInvitedRealm)
        {
            $string = sprintf(
                'We have declined an alliance invitation from %s.',
                $this->generateRealmOnlyString($inviter, 'neutral', $viewer)
              );
        }
        else
        {
            $string = sprintf(
                'An alliance between two foreign realms has been declined.'
              );
        }

        return $string;
    }

    public function generateAllianceOfferRescindedString(Realm $invited, Realm $inviter, Dominion $viewer): string
    {
        $viewerIsInviterRealm = ($inviter->id == $viewer->realm->id);
        $viewerIsInvitedRealm = ($invited->id == $viewer->realm->id);

        if($viewerIsInviterRealm)
        {
            $string = sprintf(
                'We have rescinded our alliance invitation from %s.',
                $this->generateRealmOnlyString($invited, 'neutral', $viewer)
              );
        }
        elseif($viewerIsInvitedRealm)
        {
            $string = sprintf(
                '%s has rescinded their alliance invitation to us.',
                $this->generateRealmOnlyString($inviter, 'neutral', $viewer)
              );
        }
        else
        {
            $string = sprintf(
                'An alliance invitation between two foreign realms has been rescinded.'
              );
        }

        return $string;
    }

    public function generateArtefactCompletedString(Realm $realm, Artefact $artefact, Dominion $viewer): string
    {
        /*
            Mirnon has accepted the devotion of Dark Elf (#3).
        */

        $artefactClass = $this->getSpanClass('info');

        $string = sprintf(
            '<span class="%s">%s</span> has been brought to the %s.',
            $artefactClass,
            $artefact->name,
            $this->generateRealmOnlyString($realm)
          );

        return $string;
    }

    public function generateBarbarianInvasionString(Dominion $dominion, GameEvent $event, Dominion $viewer): string
    {
        /*
             Gilleg's Herd (#1) ransacked a nearby merchant outpost and captured 235 land.
        */

        $string = sprintf(
            '%s %s a nearby %s and captured <b><span class="%s">%s</span></b> land.',
            $this->generateDominionString($dominion, 'barbarian', $viewer),
            $event['data']['type'],
            $event['data']['target'],
            $this->getSpanClass('barbarian'),
            number_format($event['data']['land']),
          );

        return $string;
    }

    public function generateCountdownString(GameEvent $countdown, Dominion $viewer): string
    {
        /*
            Mirnon has accepted the devotion of Dark Elf (#3).
        */

        $round = $countdown->round;
        $trigger = $countdown->source;

        if(in_array($round->mode, ['standard-duration', 'deathmatch-duration', 'factions-duration', 'packs-duration']))
        {
            return sprintf(
                '<span class="%s">%s</span> has reached %s %s and triggered the the countdown! The round ends in 48 ticks.',
                $this->getSpanClass('green'),
                $this->generateDominionString($trigger, 'neutral', $viewer),
                number_format($round->goal),
                $this->roundHelper->getRoundModeGoalString($round)
            );
        }

        if(in_array($round->mode, ['standard', 'deathmatch','factions','packs']))
        {
            return sprintf(
                '<span class="%s">%s</span> has reached %s %s and triggered the the countdown! The round ends in 48 ticks, at tick %s.',
                $this->getSpanClass('green'),
                $this->generateDominionString($trigger, 'neutral', $viewer),
                number_format($round->goal),
                $this->roundHelper->getRoundModeGoalString($round),
                number_format($round->end_tick)
            );
        }

    }

    public function generateDecreeIssuedString(Dominion $issuer, GameEvent $decreeIssuedEvent, Dominion $viewer): string
    {

        $viewerInvolved = ($issuer->realm->id == $viewer->realm->id);

        if(!$viewerInvolved)
        {
            return sprintf(
                'A new decree has been issued in the %s realm.',
                $this->generateRealmOnlyString($issuer->realm)
              );
        }
        else
        {
            $decree = $decreeIssuedEvent->target;
            $decreeState = DecreeState::findOrFail($decreeIssuedEvent->data['decree_state_id']);

            return sprintf(
                '%s has issued a decree regarding %s: <span class="text-green">%s</span>.',
                $this->generateDominionString($issuer, 'friendly', $viewer),
                $decree->name,
                $decreeState->name
              );
        }
    }

    public function generateDecreeRevokedString(Dominion $issuer, GameEvent $decreeIssuedEvent, Dominion $viewer): string
    {

        $deityClass = $this->getSpanClass('other');

        $viewerInvolved = ($issuer->realm->id == $viewer->realm->id);

        if(!$viewerInvolved)
        {
            return sprintf(
                'A decree has been revoked in the %s realm.',
                $this->generateRealmOnlyString($issuer->realm)
              );
        }
        else
        {
            $decree = $decreeIssuedEvent->target;
            $decreeState = DecreeState::findOrFail($decreeIssuedEvent->data['decree_state_id']);
            return sprintf(
                '%s has revoked the %s decree.',
                $this->generateDominionString($issuer, 'friendly', $viewer),
                $decree->name,
                $decreeState->name
              );
        }
    }

    public function generateDeityCompletedString(Dominion $dominion, Deity $deity, Dominion $viewer): string
    {
        /*
            Mirnon has accepted the devotion of Dark Elf (#3).
        */

        $deityClass = $this->getSpanClass('other');

        $string = sprintf(
            '<span class="%s">%s</span> has accepted the devotion of %s.',
            $deityClass,
            $deity->name,
            $this->generateDominionString($dominion, 'neutral', $viewer)
          );

        return $string;
    }

    public function generateDeityRenouncedString(Dominion $dominion, Deity $deity, Dominion $viewer): string
    {
        /*
            Winter Soldier (#2) has renounced Bregon.
        */

        $deityClass = $this->getSpanClass('other');

        $string = sprintf(
            '%s has renounced <span class="%s">%s</span>.',
            $this->generateDominionString($dominion, 'neutral', $viewer),
            $deityClass,
            $deity->name,
          );

        return $string;
    }

    public function generateDesecrationString(Dominion $desecrator, GameEvent $desecration, Dominion $viewer): string
    {
        /*
            %s units have desecrated a battlefield.
            %s has desecrated a battlefield.
        */

        $mode = 'other';
        if($desecrator->realm->id == $viewer->realm->id)
        {
            $mode = 'green';
        }

        $originalEvent = GameEvent::findOrFail($desecration->data['game_event_id']);

        $eventTypeString = $this->desecrationHelper->getDesecrationTargetTypeString($originalEvent);

        $viewerInvolved = ($desecrator->realm->id == $viewer->realm->id);

        #dump($desecrator->realm->id, $viewer->realm->id, $viewerInvolved, $desecrator->name, $viewer->name, $desecrator->realm->id == $viewer->realm->id);

        if($viewerInvolved)
        {
            $string = sprintf(
                '%s has desecrated a %s.',
                $this->generateDominionString($desecrator, 'friendly', $viewer),
                $eventTypeString
              );
        }
        else
        {
            $string = sprintf(
                '%s units have desecrated a %s.',
                $this->raceHelper->getRaceAdjective($desecrator->race),
                $eventTypeString
            );
        }

        return $string;
    }

    public function generateExpeditionString(Dominion $dominion, GameEvent $expedition, Dominion $viewer): string
    {
        /*
            An expedition was sent out by Golden Showers (#2), discovering 4 land.
        */

        $mode = 'other';
        if($dominion->realm->id == $viewer->realm->id)
        {
            $mode = 'green';
        }

        if(isset($expedition['data']['artefact']) and $expedition['data']['artefact']['found'])
        {

            $string = sprintf(
                'An expedition sent out by %s discovered <strong class="%s">%s</strong> land and found an artefact: <span class="%s">%s</span>.',
                $this->generateDominionString($dominion, 'neutral', $viewer),
                $this->getSpanClass($mode),
                number_format($expedition['data']['land_discovered']),
                $this->getSpanClass('info'),
                Artefact::findOrFail($expedition['data']['artefact']['id'])->name
              );

        }
        else
        {

            $string = sprintf(
                'An expedition sent out by %s discovered <strong class="%s">%s</strong> land.',
                $this->generateDominionString($dominion, 'neutral', $viewer),
                $this->getSpanClass($mode),
                number_format($expedition['data']['land_discovered'])
              );

        }

        return $string;
    }

    public function generateGovernorString(Realm $realm, Dominion $monarch, Dominion $viewer): string
    {
        /*
            Dominion (# 2) has been elected governor of the realm.
        */

        $mode = 'other';
        if($realm->id == $viewer->realm->id)
        {
            $mode = 'green';
        }

        $string = sprintf(
            '%s %s governor of the %s realm.',
            $this->generateDominionString($monarch, 'neutral', $viewer),
            in_array($realm->round->mode, ['deathmatch', 'deathmatch-duration']) ? 'is now the' : 'has been elected',
            in_array($realm->round->mode, ['deathmatch', 'deathmatch-duration']) ? 'the' : 'their', 
          );

        return $string;
    }

    public function generateInvasionSupportString(Dominion $supporter, Dominion $legion, GameEvent $invasion, Dominion $viewer): string
    {
        return sprintf(
            'Forces from %s rushed to aid the Imperial Legion %s.',
            $this->generateDominionString($supporter, 'neutral', $viewer),
            $this->generateDominionString($legion, 'neutral', $viewer)
          );
    }

    public function generateInvasionString(Dominion $attacker, Dominion $defender, GameEvent $invasion, Dominion $viewer): string
    {
        $landConquered = 0;
        $landDiscovered = 0;

        $isAttackerFriendly = ($attacker->realm->id == $viewer->realm->id);
        $isDefenderFriendly = ($defender->realm->id == $viewer->realm->id);

        if ($isSuccessful = $invasion['data']['result']['success'])
        {
            $landConquered += intval($invasion['data']['attacker']['land_conquered']);

            $landDiscovered += intval($invasion['data']['attacker']['land_discovered']);
            $landDiscovered += intval($invasion['data']['attacker']['extra_land_discovered']);
        }

        # Deathmatch in-realm sucessful invasion
        if($isSuccessful and ($viewer->round->mode == 'deathmatch' or $viewer->round->mode == 'deathmatch-duration'))
        {
            $spanClass = 'purple';
            if($defender->id == $viewer->id)
            {
                $spanClass = 'red';
            }
            if($attacker->id == $viewer->id)
            {
                $spanClass = 'green';
            }

            return sprintf(
                '%s conquered <strong class="%s">%s</strong> land from %s.',
                $this->generateDominionString($attacker, 'neutral', $viewer),
                $this->getSpanClass($spanClass),
                number_format($landConquered),
                $this->generateDominionString($defender, 'neutral', $viewer)
              );
        }

        # Deathmatch in-realm unsucessful invasion
        if(!$isSuccessful and ($viewer->round->mode == 'deathmatch' or $viewer->round->mode == 'deathmatch-duration'))
        {
            return sprintf(
                '%s fended off an attack by %s.',
                $this->generateDominionString($defender, 'neutral', $viewer),
                $this->generateDominionString($attacker, 'neutral', $viewer)
              );
        }

        # Friendly attacker successful
        if($isAttackerFriendly and !$isDefenderFriendly and $isSuccessful)
        {
            return sprintf(
                'Victorious in battle, %s %s <strong class="text-green">%s</strong> land from %s and discovered <strong class="text-orange">%s</strong> land.',
                $this->generateDominionString($attacker, 'neutral', $viewer),
                $this->getVictoryString($invasion),
                number_format($landConquered),
                $this->generateDominionString($defender, 'neutral', $viewer),
                number_format($landDiscovered)
              );
        }
        # Friendly attacker unsuccessful
        if($isAttackerFriendly and !$isDefenderFriendly and !$isSuccessful)
        {
            return sprintf(
                '%s was beaten back by %s.',
                $this->generateDominionString($attacker, 'neutral', $viewer),
                $this->generateDominionString($defender, 'neutral', $viewer)
              );
        }
        # Friendly defender successful
        if(!$isAttackerFriendly and $isDefenderFriendly and !$isSuccessful)
        {
            return sprintf(
                '%s fended off an attack by %s.',
                $this->generateDominionString($defender, 'neutral', $viewer),
                $this->generateDominionString($attacker, 'neutral', $viewer)
              );
        }
        # Friendly defender unsuccessful
        if(!$isAttackerFriendly and $isDefenderFriendly and $isSuccessful)
        {
            return sprintf(
                '%s conquered <strong class="text-red">%s</strong> land from %s.',
                $this->generateDominionString($attacker, 'neutral', $viewer),
                number_format($landConquered),
                $this->generateDominionString($defender, 'neutral', $viewer)
              );
        }

        # Hostile attacker successful against hostile defender
        if(!$isAttackerFriendly and !$isDefenderFriendly and $isSuccessful)
        {
            return sprintf(
                '%s conquered <strong class="text-orange">%s</strong> land from %s.',
                $this->generateDominionString($attacker, 'neutral', $viewer),
                number_format($landConquered),
                $this->generateDominionString($defender, 'neutral', $viewer)
              );
        }

        # Hostile attacker unsuccessful against hostile defender
        if(!$isAttackerFriendly and !$isDefenderFriendly and !$isSuccessful)
        {
            return sprintf(
                '%s fended off an attack by %s.',
                $this->generateDominionString($defender, 'neutral', $viewer),
                $this->generateDominionString($attacker, 'neutral', $viewer)
              );
        }

        return 'Edge case detected for GameEvent ID ' . $invasion->id;

    }

    public function generateNewDominionString(Dominion $dominion, Realm $realm, Dominion $viewer): string
    {
        /*
            The Barbarian dominion of Ssiwen's Mongrels, led by Commander Ssiwen, was spotted in the Barbarian Horde.
        */

        $mode = 'hostile';
        if(($dominion->realm->id == $viewer->realm->id))
        {
            $mode = 'green';
        }

        $string = sprintf(
            'The <span class="%s">%s</span> dominion of %s was founded by <em>%s</em> %s.',
            $this->getSpanClass($mode),
            $this->raceHelper->getRaceAdjective($dominion->race),
            $this->generateDominionString($dominion, 'neutral', $viewer),
            $dominion->title->name,
            $dominion->ruler_name
          );

        return $string;
    }

    public function generateNoGovernorString(Dominion $viewer, Realm $realm): string
    {
        /*
            Realm (# x) no longer has a governor.
        */

        $viewerInvolved = ($viewer->realm->id == $realm->id);

        if(!$viewerInvolved)
        {
            $string = sprintf(
                '%s realm no longer has a governor.',
                $this->generateRealmOnlyString($realm)
              );
        }
        else
        {
            $string = sprintf(
                'Our realm no longer has a governor.',
              );
        }

        return $string;
        return $string;
    }


    public function generateProtectorshipOfferedString(Dominion $protected, Dominion $protector, Dominion $viewer): string
    {
        /*
            Dark Elf (# 3) has offered to protect Artillery (# 3).
        */

        $viewerInvolved = ($protector->realm->id == $viewer->realm->id or $protected->realm->id == $viewer->realm->id);

        if(!$viewerInvolved)
        {
            $string = sprintf(
                'A protectorship offer has been made in the %s realm.',
                $this->generateRealmOnlyString($protected->realm)
              );
        }
        else
        {
            $string = sprintf(
                '%s has offered to protect %s.',
                $this->generateDominionString($protector, 'neutral', $viewer),
                $this->generateDominionString($protected, 'neutral', $viewer)
              );
        }

        return $string;
    }

    public function generateProtectorshipAcceptedString(Dominion $protected, Dominion $protector, Dominion $viewer): string
    {
        /*
            Artillery (# 3) is now under the protection of Orc (# 3).
        */


        $string = sprintf(
            '%s is now under the protection of %s.',
            $this->generateDominionString($protected, 'neutral', $viewer),
            $this->generateDominionString($protector, 'neutral', $viewer)
            );

        return $string;
    }

    public function generateProtectorshipDeclinedString(Dominion $protected, Dominion $protector, Dominion $viewer): string
    {
        /*
            Artillery (# 3) has declined a protectorship offer by Orc (# 3).
        */

        $viewerInvolved = ($protector->realm->id == $viewer->realm->id or $protected->realm->id == $viewer->realm->id);

        if(!$viewerInvolved)
        {
            $string = sprintf(
                'A protectorship offer has been declined in the %s realm.',
                $this->generateRealmOnlyString($protected->realm)
              );
        }
        else
        {
            $string = sprintf(
                '%s has has declined a protectorship offer by %s.',
                $this->generateDominionString($protected, 'neutral', $viewer),
                $this->generateDominionString($protector, 'neutral', $viewer)
              );
        }

        return $string;
    }

    public function generateResearchCompletedString(Dominion $researcher, Tech $tech, Dominion $viewer): string
    {
        /*
            Dark Elf (#3) has completed research of Reinforced Tools
        */

        $viewerInvolved = ($researcher->realm->id == $viewer->realm->id or $researcher->realm->id == $viewer->realm->id);

        if(!$viewerInvolved)
        {
            $string = sprintf(
                'Research has been completed in the %s realm.',
                $this->generateRealmOnlyString($researcher->realm)
              );
        }
        else
        {
            $string = sprintf(
                '%s has completed research of %s.',
                $this->generateDominionString($researcher, 'neutral', $viewer),
                $tech->name
              );
        }

        return $string;
    }

    public function generateSabotageString(Dominion $caster, Dominion $target, GameEvent $sorcery, Dominion $viewer): string
    {

        $spyop = Spyop::where('key', $sorcery['data']['spyop_key'])->first();

        # Viewer can see caster if viewer is in same realm as caster, or if viewer is in same realm as target and taret has reveal_ops

        $viewerInvolved = ($caster->realm->id == $viewer->realm->id or $target->realm->id == $viewer->realm->id);

        if(!$viewerInvolved)
        {
            return sprintf(
                '<span class="text-red">%s</span> operation performed in the %s realm.',
                $spyop->name,
                $this->generateRealmOnlyString($target->realm)
              );
        }

        $canViewerSeeCaster = false;
        if(($caster->realm->id == $viewer->realm->id) or ($target->realm->id == $viewer->realm->id and $sorcery['data']['target']['reveal_ops']))
        {
            $canViewerSeeCaster = true;
        }

        if($viewer->realm->id == $caster->realm->id)
        {
            $spyopSpanClass = $this->getSpanClass('green');
        }
        elseif($viewer->realm->id == $target->realm->id)
        {
            $spyopSpanClass = $this->getSpanClass('hostile');
        }
        else
        {
            $spyopSpanClass = $this->getSpanClass('neutral');
        }

        if($canViewerSeeCaster)
        {
            $string = sprintf(
              '%s performed <span class="%s">%s</span> on %s.',
              $this->generateDominionString($caster, 'neutral', $viewer),
              $spyopSpanClass,
              $spyop->name,
              $this->generateDominionString($target, 'neutral', $viewer)
            );
        }
        else
        {
            $string = sprintf(
              '<span class="%s">%s</span> operation performed on %s.',
              $spyopSpanClass,
              $spyop->name,
              $this->generateDominionString($target, 'neutral', $viewer)
            );
        }

        return $string;
    }

    public function generateSorceryString(Dominion $caster, Dominion $target, GameEvent $sorcery, Dominion $viewer): string
    {
        /*
            Mirnon has accepted the devotion of Dark Elf (#3).
        */

        $spell = Spell::where('key', $sorcery['data']['spell_key'])->first();

        # Viewer can see caster if viewer is in same realm as caster, or if viewer is in same realm as target and taret has reveal_ops

        $viewerInvolved = ($caster->realm->id == $viewer->realm->id or $target->realm->id == $viewer->realm->id);

        if(!$viewerInvolved)
        {
            return sprintf(
                '<span class="text-red">%s</span> cast on a dominion in the %s realm.',
                $spell->name,
                $this->generateRealmOnlyString($target->realm)
              );
        }

        $canViewerSeeCaster = false;
        if(($caster->realm->id == $viewer->realm->id) or ($target->realm->id == $viewer->realm->id and $sorcery['data']['target']['reveal_ops']))
        {
            $canViewerSeeCaster = true;
        }

        if($viewer->realm->id == $caster->realm->id)
        {
            $spellSpanClass = $this->getSpanClass('green');
        }
        elseif($viewer->realm->id == $target->realm->id)
        {
            $spellSpanClass = $this->getSpanClass('hostile');
        }
        else
        {
            $spellSpanClass = $this->getSpanClass('neutral');
        }

        if($canViewerSeeCaster)
        {
            $string = sprintf(
              '%s cast <span class="%s">%s</span> on %s.',
              $this->generateDominionString($caster, 'neutral', $viewer),
              $spellSpanClass,
              $spell->name,
              $this->generateDominionString($target, 'neutral', $viewer)
            );
        }
        else
        {
            $string = sprintf(
              '<span class="%s">%s</span> was cast on %s.',
              $spellSpanClass,
              $spell->name,
              $this->generateDominionString($target, 'neutral', $viewer)
            );
        }

        return $string;
    }

    public function generateTheftString(Dominion $thief, Dominion $target, GameEvent $theft, Dominion $viewer): string
    {
        /*
            Spies from Birka (#2) stole 256,000 gold from Zigwheni (#2).
        */

        $viewerInvolved = ($thief->realm->id == $viewer->realm->id or $target->realm->id == $viewer->realm->id);

        if(!$viewerInvolved)
        {
            return sprintf(
                'Theft reported in the %s realm.',
                $this->generateRealmOnlyString($target->realm)
              );
        }

        if($thief->realm->id == $viewer->realm->id)
        {
            $amountClass = $this->getSpanClass('green');
        }
        else
        {
            $amountClass = $this->getSpanClass('hostile');
        }

        $amount = $theft['data']['amount_stolen'];
        $resourceName = $theft['data']['resource']['name'];

        $string = sprintf(
            'Spies from %s stole <b><span class="%s">%s</span></b> %s from %s.',
            $this->generateDominionString($thief, 'neutral', $viewer),
            $amountClass,
            number_format($amount),
            $resourceName,
            $this->generateDominionString($target, 'neutral', $viewer),
          );

        return $string;
    }

    public function generateDominionString(Dominion $dominion, string $mode = "neutral", Dominion $viewer): string
    {

        $string = sprintf(
            '<a href="%s">
                <span data-toggle="tooltip" data-placement="top" title="
                    <small class=\'text-muted\'>Range:</small> <span class=\'%s\'>%s%%</span>&nbsp;<small>(%s)</small><br>
                    <small class=\'text-muted\'>Faction:</small> %s<br>
                    <small class=\'text-muted\'>Status:</small> %s<br>
                    <small class=\'text-muted\'>Units returning:</small> %s<br>
                    <small class=\'text-muted\'>Ruler:</small> <em>%s</em> %s"
                class="%s"> %s
                    <a href="%s">
                        (# %s)
                    </a>
                </span>
            </a>',
            route('dominion.insight.show', [$dominion->id]),
            $this->rangeCalculator->isInRange($viewer, $dominion) ? "text-green" : "text-red",
            number_format($dominion->land/$viewer->land*100,2),
            number_format($dominion->land),
            $dominion->race->name,
            (in_array($dominion->round->mode, ['deathmatch','deathmatch-duration']) or ($dominion->realm->id !== $viewer->realm->id and !$dominion->realm->getAllies()->contains($viewer->realm))  ) ? "<span class='text-red'>Hostile</span>" : ($dominion->realm->getAllies()->contains($viewer->realm) ? "<span class='text-green'>Ally</span>" : "<span class='text-green'>Friendly</span>"),
            $this->militaryCalculator->hasReturningUnits($dominion) ? "<span class='text-green'>Yes</span>" : "<span class='text-red'>No</span>",
            $dominion->title->name,
            $dominion->ruler_name,
            $this->getSpanClass($mode),
            $dominion->name,
            route('dominion.realm', [$dominion->realm->number]),
            $dominion->realm->number
          );

        return $string;
    }

    public function generateRealmOnlyString(Realm $realm, $mode = 'other'): string
    {

        if(in_array($realm->round->mode, ['packs', 'packs-duration']))
        {

            if($realm->alignment === 'npc')
            {
                $realmString = 'Barbarians';
            }
            else
            {
                $realmString = Dominion::where('user_id', $realm->pack->leader->user_id)->where('round_id',$realm->round->id)->first()->ruler_name . "'s pack";
            }

            $string = sprintf(
                '<a href="%s"><span class="%s">%s</span> (# %s)</a>',
                route('dominion.realm', [$realm->number]),
                $this->getSpanClass($mode),
                $realmString,
                $realm->number
              );
        }
        else
        {
            $string = sprintf(
                '<a href="%s"><span class="%s">%s</span> (# %s)</a>',
                route('dominion.realm', [$realm->number]),
                $this->getSpanClass($mode),
                $this->realmHelper->getAlignmentAdjective($realm->alignment),
                $realm->number
              );
        }

        return $string;
    }

    public function getSpanClass(string $mode = 'neutral'): string
    {
        switch ($mode)
        {
            case 'hostile':
            case 'red':
                return 'text-red';

            case 'friendly':
            case 'neutral':
            case 'aqua':
                return 'text-aqua';

            case 'green':
                return 'text-green';

            case 'barbarian':
            case 'other':
                return 'text-orange';

            case 'purple':
                return 'text-purple';

            default:
                return 'text-aqua';
        }
    }

    private function getVictoryString(GameEvent $invasion): string
    {

        if(isset($invasion['data']['result']['annexation']) and $invasion['data']['result']['annexation'])
        {
            return 'annexed and conquered';
        }

        if(isset($invasion['data']['attacker']['liberation']) and $invasion['data']['attacker']['liberation'])
        {
            return 'liberated and conquered';
        }

        if(isset($invasion['data']['result']['isAmbush']) and $invasion['data']['result']['isAmbush'])
        {
            return 'ambushed and conquered';
        }

        if($invasion['data']['result']['op_dp_ratio'] >= (1/0.85))
        {
            return 'easily conquered';
        }

        return 'conquered';
    }

    public function getWorldNewsEventKeyDescriptions(): array
    {
        return [
            'abandon_dominion' => 'Dominion abandoned',
            #'artefact_completed' => 'Artefact arrival',
            'barbarian_invasion' =>'Barbarian invasion',
            'decree_issued' => 'Decree issued',
            'decree_revoked' => 'Decree revoked',
            'deity_completed' => 'Deity completed',
            'deity_renounced' => 'Deity renounced',
            'desecration' => 'Desecration',
            'expedition' => 'Expedition',
            'governor' => 'Governor appointment',
            'invasion' => 'Invasion',
            'invasion_support' => 'Invasion support',
            'new_dominion' => 'New dominion',
            'protectorship_offered' => 'Protectorship offer',
            'protectorship_accepted' => 'Protectorate established',
            'protectorship_declined' => 'Protectorship declined',
            'research_completed' => 'Research completed',
            'round_countdown_duration' => 'Round countdown (fixed length rounds)',
            'round_countdown' => 'Round countdown (land target rounds)',
            'sabotage' => 'Sabotage',
            'sorcery' => 'Sorcery',
            'theft' =>' Theft',
        ];
    }

    public function getWorldNewsEventDescription(string $eventKey): string
    {
        return isset($this->getWorldNewsEventKeyDescriptions()[$eventKey]) ? $this->getWorldNewsEventKeyDescriptions()[$eventKey] : $eventKey;
    }

    public function getDefaultUserWorldNewsSettings(): array
    {
        $scopes = ['own', 'other'];
        $defaultSettings = [];

        foreach($scopes as $scope)
        {
            foreach($this->getWorldNewsEventKeyDescriptions() as $eventKey => $eventDescription)
            {
                $defaultSettings[$scope . '.' . $eventKey] = true;
            }
        }

        return $defaultSettings;
    }

    public function getWorldNewsEventIcon(string $eventType): string
    {
        # Switch cases for $eventType
       
        switch ($eventType) {
            case 'invasion':
                return 'ra ra-crossed-swords ra-fw';
            case 'expedition':
                return 'fas fa-drafting-compass fa-fw';
            case 'theft':
                return 'fas fa-hand-lizard fa-fw';
            case 'sorcery':
                return 'fas fa-hat-wizard fa-fw';
            case 'sabotage':
                return 'fa fa-user-secret fa-fw';
            case 'desecration':
                return 'ra ra-tombstone ra-fw';
            default:
                return '';
        }
    }

}
