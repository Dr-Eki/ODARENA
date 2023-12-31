<?php

namespace OpenDominion\Helpers;

use LogicException;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Spell;

/* TBD:

    Hide/Show Barbarian Events
    Hide/Show Active Spells in Menu/Top Bar
    Hide/Show Confirm Release Units?
    Hide/Show Confirm Destroy Buildings?

*/

class SettingHelper
{
    /** @var MilitaryHelper */
    protected $militaryHelper;

    public function __construct()
    {
        $this->militaryHelper = app(MilitaryHelper::class);
    }

    public function getNotificationCategories(): array
    {
        return [
            'general' => $this->getGeneralTypes(),
            'hourly_dominion' => $this->getHourlyDominionTypes(),
            'irregular_dominion' => $this->getIrregularDominionTypes(),
            'irregular_realm' => $this->getIrregularRealmTypes(),
        ];
    }

    public function getNotificationTypeLabel(string $key): string
    {
        return [
            'general' => 'General Notifications',
            'hourly_dominion' => 'Tick Notifications',
            'irregular_dominion' => 'Event Notifications',
            'irregular_realm' => 'Realm Notifications',
        ][$key];
    }

    public function getGeneralTypes(): array
    {
        return [
            // updates
            // anouncements
            'generic' => [
                'label' => 'Generic emails manually sent by the administrators',
                'onlyemail' => true,
                'defaults' => ['email' => true],
            ]
        ];
    }

    public function getHourlyDominionTypes(): array
    {
        return [
            'exploration_completed' => [
                'label' => 'Land exploration completed',
                'defaults' => ['email' => false, 'ingame' => true],
                'route' => route('dominion.land'),
                'iconClass' => 'fa fa-search text-green',
            ],
            'exploration_completed' => [
                'label' => 'Expedition completed',
                'defaults' => ['email' => false, 'ingame' => true],
                'route' => route('dominion.land'),
                'iconClass' => 'fas fa-hiking text-green',
            ],
            'construction_completed' => [
                'label' => 'Building construction completed',
                'defaults' => ['email' => false, 'ingame' => true],
                'route' => route('dominion.buildings'),
                'iconClass' => 'fa fa-home text-green',
            ],
            'training_completed' => [
                'label' => 'Military training completed',
                'defaults' => ['email' => false, 'ingame' => true],
                'route' => route('dominion.military'),
                'iconClass' => 'ra ra-muscle-up text-green',
            ],
            'summoning_completed' => [
                'label' => 'Unit summoning/generation completed',
                'defaults' => ['email' => false, 'ingame' => true],
                'route' => route('dominion.military'),
                'iconClass' => 'ra ra-muscle-up text-green',
            ],
            'stun_completed' => [
                'label' => 'Stunned units recovered',
                'defaults' => ['email' => false, 'ingame' => true],
                'route' => route('dominion.military'),
                'iconClass' => 'ra ra-aware text-green',
            ],
            'sabotage_completed' => [
                'label' => 'Sabotage restored',
                'defaults' => ['email' => false, 'ingame' => true],
                'route' => route('dominion.sabotage'),
                'iconClass' => 'fa fa-user-secret fa-fw text-green',
            ],
            'returning_completed' => [
                'label' => 'Units returned from battle',
                'defaults' => ['email' => false, 'ingame' => true],
                'route' => route('dominion.military'),
                'iconClass' => 'ra ra-boot-stomp text-green',
            ],
            'repair_completed' => [
                'label' => 'Repair completed',
                'defaults' => ['email' => false, 'ingame' => true],
                'route' => route('dominion.buildings'),
                'iconClass' => 'ra ra-repair ra-fw text-green',
            ],
            'restore_completed' => [
                'label' => 'Restoration completed',
                'defaults' => ['email' => false, 'ingame' => true],
                'route' => route('dominion.buildings'),
                'iconClass' => 'ra ra-gear-hammer ra-fw text-green',
            ],
            'beneficial_magic_dissipated' => [
                'label' => 'Beneficial magic effect dissipated',
                'defaults' => ['email' => false, 'ingame' => true],
                'route' => route('dominion.magic'),
                'iconClass' => 'ra ra-fairy-wand text-orange',
            ],
            'harmful_magic_dissipated' => [
                'label' => 'Harmful magic effect dissipated',
                'defaults' => ['email' => false, 'ingame' => true],
                'iconClass' => 'ra ra-fairy-wand text-green',
            ],
            'starvation_occurred' => [
                'label' => 'Starvation occurred',
                'defaults' => ['email' => false, 'ingame' => true],
                'route' => route('dominion.resources'),
                'iconClass' => 'ra ra-apple text-red',
            ],
            'attrition_occurred' => [
                'label' => 'Attrition occurred',
                'defaults' => ['email' => false, 'ingame' => true],
                'route' => route('dominion.military'),
                'iconClass' => 'ra ra-interdiction text-orange',
            ],
            'treachery_completed' => [
                'label' => 'Resources from treasonous spies have arrived',
                'defaults' => ['email' => false, 'ingame' => true],
                'route' => route('dominion.resources'),
                'iconClass' => 'ra ra-aware text-green',
            ],
            'received_invasion' => [
                'label' => 'Your dominion got invaded.',
                'defaults' => ['email' => false, 'ingame' => true],
                'route' => route('dominion.status'),
                'iconClass' => 'ra ra-crossed-swords text-red',
            ],
            'repelled_invasion' => [
                'label' => 'Your dominion repelled an invasion.',
                'defaults' => ['email' => false, 'ingame' => true],
                'route' => route('dominion.status'),
                'iconClass' => 'ra ra-crossed-swords text-orange',
            ],
        ];
    }

    public function getIrregularDominionTypes(): array
    {
        return [
            'received_invasion' => [
                'label' => 'Your dominion got invaded',
                'defaults' => ['email' => false, 'ingame' => true],
                'route' => function (array $routeParams) {
                    return route('dominion.event', $routeParams);
                },
                'iconClass' => 'ra ra-crossed-swords text-red',
            ],
            'repelled_invasion' => [
                'label' => 'Your dominion repelled an invasion',
                'defaults' => ['email' => false, 'ingame' => true],
                'route' => function (array $routeParams) {
                    return route('dominion.event', $routeParams);
                },
                'iconClass' => 'ra ra-crossed-swords text-orange',
            ],
            'theft' => [
                'label' => 'Your dominion was stolen from',
                'defaults' => ['email' => false, 'ingame' => true],
                'route' => function (array $routeParams) {
                    return route('dominion.event', $routeParams);
                },
                'iconClass' => 'fa fa-hand-lizard text-red',
            ],
            'sorcery' => [
                'label' => 'Hostile wizards have performed sorcery on us',
                'defaults' => ['email' => false, 'ingame' => true],
                'route' => function (array $routeParams) {
                    return route('dominion.event', $routeParams);
                },
                'iconClass' => 'fas fa-hat-wizard text-red',
            ],
            'received_spy_op' => [
                'label' => 'Hostile spy operation received',
                'defaults' => ['email' => false, 'ingame' => true],
                'iconClass' => 'fa fa-user-secret text-orange',
            ],
            'repelled_spy_op' => [
                'label' => 'Hostile spy operation repelled',
                'defaults' => ['email' => false, 'ingame' => true],
                'iconClass' => 'fa fa-user-secret text-orange',
            ],
            'resource_theft' => [
                'label' => 'Resource stolen',
                'defaults' => ['email' => false, 'ingame' => true],
                'iconClass' => 'fa fa-user-secret text-orange',
            ],
            'repelled_resource_theft' => [
                'label' => 'Resource theft repelled',
                'defaults' => ['email' => false, 'ingame' => true],
                'iconClass' => 'fa fa-user-secret text-orange',
            ],
            'received_hostile_spell' => [
                'label' => 'Hostile spell received',
                'defaults' => ['email' => false, 'ingame' => true],
                'iconClass' => 'ra ra-fairy-wand text-orange',
            ],
            'received_sorcery' => [
                'label' => 'Received sorcery',
                'defaults' => ['email' => false, 'ingame' => true],
                'iconClass' => 'fas fa-hat-wizard text-red',
            ],
            'repelled_hostile_spell' => [
                'label' => 'Hostile spell deflected',
                'defaults' => ['email' => false, 'ingame' => true],
                'iconClass' => 'ra ra-fairy-wand text-orange',
            ],
            'reflected_hostile_spell' => [
                'label' => 'Hostile spell reflected',
                'defaults' => ['email' => false, 'ingame' => true],
                'iconClass' => 'ra ra-fairy-wand text-green',
            ],
            'received_friendly_spell' => [
                'label' => 'Friendly spell received',
                'defaults' => ['email' => false, 'ingame' => true],
                'iconClass' => 'ra ra-fairy-wand text-green',
            ],
            'received_protectorship_offer' => [
                'label' => 'Protectorship offer received',
                'defaults' => ['email' => false, 'ingame' => true],
                'iconClass' => 'fas fa-user-shield text-green',
            ],
            'received_protectorship_offer_accepted' => [
                'label' => 'Protectorship offer accepted',
                'defaults' => ['email' => false, 'ingame' => true],
                'iconClass' => 'fas fa-user-shield text-green',
            ],
            'received_protectorship_offer_declined' => [
                'label' => 'Protectorship offer declined',
                'defaults' => ['email' => false, 'ingame' => true],
                'iconClass' => 'fas fa-user-shield',
            ],
            'received_alliance_offer' => [
                'label' => 'Alliance offer received',
                'defaults' => ['email' => false, 'ingame' => true],
                'iconClass' => 'fa fa-handshake text-green',
            ],
            'received_alliance_offer_accepted' => [
                'label' => 'Alliance offer accepted',
                'defaults' => ['email' => false, 'ingame' => true],
                'iconClass' => 'fa fa-handshake text-green',
            ],
            'received_alliance_offer_declined' => [
                'label' => 'Alliance offer declined',
                'defaults' => ['email' => false, 'ingame' => true],
                'iconClass' => 'fa fa-handshake',
            ],

            # Cult
            'enthralling_occurred' => [
                'label' => 'Enthralling occurred',
                'defaults' => ['email' => false, 'ingame' => true],
                #'route' => route('dominion.military'),
                'iconClass' => 'ra ra-aware text-green',
            ],
            'persuasion_occurred' => [
                'label' => 'Perusasion occurred',
                'defaults' => ['email' => false, 'ingame' => true],
                #'route' => route('dominion.military'),
                'iconClass' => 'ra ra-aware text-green',
            ],
            'cogency_occurred' => [
                'label' => 'Cogency occurred',
                'defaults' => ['email' => false, 'ingame' => true],
                #'route' => route('dominion.military'),
                'iconClass' => 'ra ra-aware text-green',
            ],
            'treachery_occurred' => [
                'label' => 'Treachery occurred',
                'defaults' => ['email' => false, 'ingame' => true],
                #'route' => route('dominion.military'),
                'iconClass' => 'ra ra-aware text-green',
            ],

            # Weres
            'spy_conversion_occurred' => [
                'label' => 'Spy conversion occurred',
                'defaults' => ['email' => false, 'ingame' => true],
                #'route' => route('dominion.military'),
                'iconClass' => 'ra ra-aware text-green',
            ],
        ];
    }

    public function getIrregularRealmTypes(): array
    {
        return [
            'enemy_realm_declared_war' => [
                'label' => 'An enemy realm declared war upon our realm',
                'defaults' => ['email' => false, 'ingame' => true],
                'iconClass' => 'ra ra-crossed-axes text-red',
            ],
            'declared_war_upon_enemy_realm' => [
                'label' => 'Our realm declared war upon an enemy realm',
                'defaults' => ['email' => false, 'ingame' => true],
                'iconClass' => 'ra ra-crossed-axes text-red',
            ],
        ];
    }

    public function getDefaultUserNotificationSettings(): array
    {
        return collect($this->getNotificationCategories())->map(function ($notifications) {
            $return = [];

            foreach ($notifications as $key => $notification) {
                $return[$key] = $notification['defaults'] ?? 'nyi';
            }

            return $return;
        })->toArray();
    }

    public function getNotificationMessage(string $category, string $type, array $data, Dominion $dominion = null): string
    {
        switch ("{$category}.{$type}") {

            case 'hourly_dominion.exploration_completed':
                $acres = array_sum($data);
                if(isset($data['xp']))
                {
                  $acres -= $data['xp'];
                }

                return sprintf(
                    'Exploration for %s %s of land completed',
                    number_format($acres),
                    str_plural('acre', $acres)
                );

            case 'hourly_dominion.construction_completed':
                $buildings = array_sum($data);

                return sprintf(
                    'Construction of %s %s completed',
                    number_format($buildings),
                    str_plural('building', $buildings)
                );

            case 'hourly_dominion.training_completed':
                $units = array_sum($data);

                return sprintf(
                    '%s of %s %s completed',
                    ucfirst($this->militaryHelper->getTrainingTerm($dominion->race)),
                    number_format($units),
                    str_plural('unit', $units)
                );

            case 'hourly_dominion.summoning_completed':
                $units = array_sum($data);

                return sprintf(
                    '%s %s %s',
                    number_format($units),
                    str_plural('unit', $units),
                    'summoned'
                );

            case 'hourly_dominion.stun_completed':
                $units = array_sum($data);

                return sprintf(
                    '%s %s have recovered from being stunned',
                    number_format($units),
                    str_plural('unit', $units)
                );

            case 'hourly_dominion.sabotage_completed':
                return sprintf(
                    '%s units have returned home from sabotage',
                    number_format(array_sum($data))
                );

            case 'hourly_dominion.repair_completed':
                return sprintf(
                    '%s sabotaged buildings have been repaired',
                    number_format(array_sum($data))
                );

            case 'hourly_dominion.restore_completed':
                return sprintf(
                    '%s sabotaged improvements have been restored',
                    number_format(array_sum($data))
                );

            case 'hourly_dominion.returning_completed':
                $units = collect($data)->filter(
                    function ($value, $key) {
                        // Disregard prestige and XP
                        if(strpos($key, 'military_') === 0) {
                            return $value;
                        }
                    }
                )->sum();

            case 'hourly_dominion.repelled_invasion':
                $attackerDominion = Dominion::with('realm')->findOrFail($data['attackerDominionId']);

                return sprintf(
                    'Forces from %s (#%s) invaded our lands, but our army drove them back! We lost %s units during the battle.',
                    $attackerDominion->name,
                    $attackerDominion->realm->number,
                    number_format(array_sum($data['units_lost']))
                );

            case 'hourly_dominion.beneficial_magic_dissipated':
                $effects = count($data);

                return sprintf(
                    '%s beneficial magic %s dissipated',
                    number_format($effects),
                    str_plural('effect', $effects)
                );

            case 'hourly_dominion.harmful_magic_dissipated':
                $effects = count($data);

                return sprintf(
                    '%s harmful magic %s dissipated',
                    number_format($effects),
                    str_plural('effect', $effects)
                );

            case 'hourly_dominion.starvation_occurred':
                return 'You do not have enough food! Morale is decreasing.';

            # CULT

            case 'hourly_dominion.attrition_occurred':
                $units = array_sum($data);
                return sprintf(
                    '%s %s have disappeared.',
                    number_format($units),
                    str_plural('unit', $units)
                );

            case 'irregular_dominion.enthralling_occurred':
                $units = $data['enthralled'];
                $sourceDominion = Dominion::with('realm')->find($data['sourceDominionId']);
                return sprintf(
                    '%s %s have abandoned %s (# %s) to join us.',
                    number_format($units),
                    str_plural('unit', $units),
                    $sourceDominion->name,
                    $sourceDominion->realm->number
                );

            case 'irregular_dominion.persuasion_occurred':
                $units = $data['persuaded'];
                $sourceDominion = Dominion::with('realm')->find($data['sourceDominionId']);
                return sprintf(
                    'We have persuaded %s %s captured from %s (# %s) to join us.',
                    number_format($units),
                    str_plural('spy', $units),
                    $sourceDominion->name,
                    $sourceDominion->realm->number
                );

            case 'irregular_dominion.cogency_occurred':
                $units = $data['saved'];
                $sourceDominion = Dominion::with('realm')->find($data['sourceDominionId']);
                return sprintf(
                    'We have rescued %s %s from %s (# %s).',
                    number_format($units),
                    str_plural('spellcaster', $units),
                    $sourceDominion->name,
                    $sourceDominion->realm->number
                );

            case 'irregular_dominion.treachery_occurred':
                $sourceDominion = Dominion::with('realm')->find($data['sourceDominionId']);
                return sprintf(
                    'Spies from %s (# %s) have stolen %s %s for us and will arrive in two ticks.',
                    $sourceDominion->name,
                    $sourceDominion->realm->number,
                    number_format($data['amount']),
                    $data['resource']
                );

            case 'hourly_dominion.treachery_completed':
                #$resources = array_keys($data);
                #$amount = number_format(intval($data[$resource]));
                #$resource = str_replace('resource_','',$resource);
                return 'Stolen resources have arrived from treacherous spies.';
                #return sprintf(
                #    'Stolen resources have arrived from treacherous spies.',
                #    $amount,
                #    $resource
                #);

            # WERES

            case 'irregular_dominion.spy_conversion_occurred':
                $units = $data['converted'];
                $sourceDominion = Dominion::with('realm')->find($data['sourceDominionId']);
                return sprintf(
                    'We have converted %s captured spy units from %s (# %s).',
                    number_format($units),
                    $sourceDominion->name,
                    $sourceDominion->realm->number
                );

            case 'irregular_dominion.received_invasion':
                $attackerDominion = Dominion::with('realm')->findOrFail($data['attackerDominionId']);

                return sprintf(
                    'An army from %s (#%s) invaded our lands, conquering %s acres of land! We lost %s units during the battle.',
                    $attackerDominion->name,
                    $attackerDominion->realm->number,
                    number_format($data['land_lost']),
                    number_format(array_sum($data['units_lost']))
                );

            case 'irregular_dominion.repelled_invasion':
                $attackerDominion = Dominion::with('realm')->findOrFail($data['attackerDominionId']);

                return sprintf(
                    'Forces from %s (#%s) invaded our lands, but our army drove them back! We lost %s units during the battle.',
                    $attackerDominion->name,
                    $attackerDominion->realm->number,
                    number_format(array_sum($data['units_lost']))
                );

            # PROTECTORSHIP

            case 'irregular_dominion.protector_received_invasion':
                $attackerDominion = Dominion::with('realm')->findOrFail($data['attackerDominionId']);
                $protectorDominion = Dominion::with('realm')->findOrFail($data['protectorDominionId']);
                return sprintf(
                    'An army from %s (#%s) invaded our lands after breaking through the defenses of %s (#%s), conquering %s acres of land.',
                    $attackerDominion->name,
                    $attackerDominion->realm->number,
                    $protectorDominion->name,
                    $protectorDominion->realm->number,
                    number_format($data['land_lost'])
                );

            case 'irregular_dominion.protector_repelled_invasion':
                $attackerDominion = Dominion::with('realm')->findOrFail($data['attackerDominionId']);
                $protectorDominion = Dominion::with('realm')->findOrFail($data['protectorDominionId']);
                return sprintf(
                    'Forces from %s (#%s) tried to invade our lands, but our protector %s (#%s) drove them back!',
                    $attackerDominion->name,
                    $attackerDominion->realm->number,
                    $protectorDominion->name,
                    $protectorDominion->realm->number
                );

            case 'irregular_dominion.received_invasion_as_protector':
                $attackerDominion = Dominion::with('realm')->findOrFail($data['attackerDominionId']);
                $protectedDominion = Dominion::with('realm')->findOrFail($data['protectedDominionId']);
                return sprintf(
                    'An army from %s (#%s) invaded our protectorate of %s (#%s), and we were unable to fight them back. We lost %s units and our protectorate lost %s land.',
                    $attackerDominion->name,
                    $attackerDominion->realm->number,
                    $protectedDominion->name,
                    $protectedDominion->realm->number,
                    number_format($data['units_lost']),
                    number_format($data['land_lost'])
                );

            case 'irregular_dominion.repelled_invasion_as_protector':
                $attackerDominion = Dominion::with('realm')->findOrFail($data['attackerDominionId']);
                $protectedDominion = Dominion::with('realm')->findOrFail($data['protectedDominionId']);
                return sprintf(
                    'An army from %s (#%s) tried to invade our protectorate of %s (#%s), but we were unable to fight them back! We lost %s units.',
                    $attackerDominion->name,
                    $attackerDominion->realm->number,
                    $protectedDominion->name,
                    $protectedDominion->realm->number,
                    number_format($data['units_lost'])
                );

            # END PROTECTORSHIP

            case 'irregular_dominion.theft':
                $thief = Dominion::with('realm')->findOrFail($data['thiefDominionId']);
                $resource = Resource::findOrFail($data['resource']);

                return sprintf(
                    'Spies from %s (#%s) have stolen %s %s from us. We killed %s of their units.',
                    $thief->name,
                    $thief->realm->number,
                    number_format($data['amountLost']),
                    $resource->name,
                    number_format(array_sum($data['unitsKilled']))
                );

            case 'irregular_dominion.sorcery':
                $spell = Spell::where('key', $data['data']['spell_key'])->first();

                if($data['data']['target']['reveal_ops'])
                {
                    $caster = Dominion::with('realm')->findOrFail($data['caster_dominion_id']);

                    return sprintf(
                        'Wizards from %s (# %s) have cast %s on us!',
                        $caster->name,
                        $caster->realm->number,
                        $spell->name,
                    );
                }
                else
                {
                    return sprintf(
                        'Wizards have cast %s on us.',
                        $spell->name,
                    );
                }


            case 'irregular_dominion.received_spy_op':
                $sourceDominion = Dominion::with('realm')->find($data['sourceDominionId']);

                switch ($data['operationKey'])
                {
                    case 'barracks_spy':
                        $resultString = 'Traces of enemy spies were detected within our barracks.';
                        break;

                    case 'castle_spy':
                        $resultString = 'Traces of enemy spies were detected within our investments.';
                        break;

                    case 'survey_dominion':
                        $resultString = 'Traces of enemy spies were detected amongst our buildings.';
                        break;

                    case 'land_spy':
                        $resultString = 'Traces of enemy spies were detected amongst our lands.';
                        break;

                    case 'assassinate_draftees':
                        $resultString = "{$data['damageString']} were assassinated while they slept in our barracks.";
                        break;

                    case 'assassinate_wizards':
                        $resultString = "{$data['damageString']} were assassinated while they slept in our towers.";
                        break;

                    case 'slaughter_draftees':
                    case 'slaughter_peasants':
                    case 'slaughter_spies':
                    case 'slaughter_wizards':
                        $resultString = "{$data['damageString']} were slaughtered.";
                        break;

                    case 'butcher_draftees':
                    case 'butcher_peasants':
                    case 'butcher_spies':
                    case 'butcher_wizards':
                        $resultString = "{$data['damageString']} were butchered.";
                        break;

                    case 'consume_draftees':
                    case 'consume_peasants':
                    case 'consume_spies':
                    case 'consume_wizards':
                        $resultString = "{$data['damageString']} were consumed.";
                        break;

                    case 'magic_snare':
                        $resultString = 'Our wizards have sensed their power diminish.';
                        break;

                    case 'sabotage_boats':
                        $resultString = "{$data['damageString']} have sunk mysteriously while docked.";
                        break;

                    case 'magic_snare':
                        $resultString = 'Our wizards have sensed their power diminish.';
                        break;

                    case 'sabotage_boats':
                        $resultString = "{$data['damageString']} have sunk mysteriously while docked.";
                        break;

                    case 'sabotage_forges':
                    case 'sabotage_walls':
                    case 'sabotage_harbor':
                        $resultString = "{$data['damageString']} have been temporarily destroyed.";
                        break;

                    default:
                        throw new LogicException("Received spy op notification for operation key {$data['operationKey']} not yet implemented");
                }

                if ($sourceDominion) {
                    return sprintf(
                        "{$resultString} Our wizards have determined that spies from %s (#%s) were responsible!",
                        $sourceDominion->name,
                        $sourceDominion->realm->number
                    );
                }

                return $resultString;

            case 'irregular_dominion.repelled_spy_op':
                $sourceDominion = Dominion::with('realm')->findOrFail($data['sourceDominionId']);

                switch ($data['operationKey']) {
                    case 'barracks_spy':
                        $where = 'within our barracks';
                        break;

                    case 'castle_spy':
                        $where = 'within our investments';
                        break;

                    case 'survey_dominion':
                        $where = 'amongst our buildings';
                        break;

                    case 'land_spy':
                        $where = 'amongst our lands';
                        break;

                    case 'assassinate_draftees':
                        $where = 'attempting to assassinate our draftees';
                        break;

                    case 'assassinate_wizards':
                        $where = 'attempting to assassinate our wizards';
                        break;

                    case 'magic_snare':
                        $where = 'attempting to sabotage our towers';
                        break;

                    case 'sabotage_boats':
                        $where = 'attempting to sabotage our boats';
                        break;

                    case 'sabotage_walls':
                    case 'sabotage_forges':
                    case 'sabotage_harbor':
                        $where = 'attempting to sabotage our improvements';
                        break;

                    default:
                        throw new LogicException("Repelled spy op notification for operation key {$data['operationKey']} not yet implemented");
                }

                $lastPart = '';
                if ($data['unitsKilled']) {
                    $lastPart = "We executed {$data['unitsKilled']}.";
                }

                return sprintf(
                    'Spies from %s (#%s) were discovered %s! %s',
                    $sourceDominion->name,
                    $sourceDominion->realm->number,
                    $where,
                    $lastPart
                );

            case 'irregular_dominion.resource_theft':
                $sourceDominion = Dominion::with('realm')->find($data['sourceDominionId']);

                switch ($data['operationKey']) {
                    case 'steal_gold':
                        $where = 'from our vaults';
                        break;

                    case 'steal_food':
                        $where = 'from our granaries';
                        break;

                    case 'steal_lumber':
                        $where = 'from our lumberyards';
                        break;

                    case 'steal_mana':
                        $where = 'from our towers';
                        break;

                    case 'steal_ore':
                    case 'steal_gems':
                        $where = 'from our mines';
                        break;

                    case 'abduct_draftees':
                        $where = 'from our barracks';
                        break;

                    case 'abduct_peasants':
                        $where = 'from our homes';
                        break;

                    case 'seize_boats':
                        $where = 'from our docks';
                        break;

                    default:
                        throw new LogicException("Resource theft op notification for operation key {$data['operationKey']} not yet implemented");
                }

                if ($sourceDominion) {
                    return sprintf(
                        'Our wizards have determined that spies from %s (#%s) stole %s %s %s!',
                        $sourceDominion->name,
                        $sourceDominion->realm->number,
                        number_format($data['amount']),
                        $data['resource'],
                        $where
                    );
                }

                return sprintf(
                    'Our spies discovered %s %s missing %s!',
                    number_format($data['amount']),
                    $data['resource'],
                    $where
                );

            case 'irregular_dominion.repelled_resource_theft':
                $sourceDominion = Dominion::with('realm')->findOrFail($data['sourceDominionId']);

                switch ($data['operationKey']) {
                    case 'steal_gold':
                        $where = 'within our vaults';
                        break;

                    case 'steal_food':
                        $where = 'within our granaries';
                        break;

                    case 'steal_lumber':
                        $where = 'within our lumberyards';
                        break;

                    case 'steal_mana':
                        $where = 'within our towers';
                        break;

                    case 'steal_ore':
                        $where = 'within our ore mines';
                        break;

                    case 'steal_gems':
                        $where = 'within our gem mines';
                        break;

                    case 'abduct_draftees':
                        $where = 'within our barracks';
                        break;

                    case 'abduct_peasants':
                        $where = 'within our homes';
                        break;

                    default:
                        throw new LogicException("Repelled resource theft op notification for operation key {$data['operationKey']} not yet implemented");
                }

                $lastPart = '';
                if ($data['unitsKilled']) {
                    $lastPart = "We executed {$data['unitsKilled']}.";
                }

                return sprintf(
                    'Spies from %s (#%s) were discovered %s! %s',
                    $sourceDominion->name,
                    $sourceDominion->realm->number,
                    $where,
                    $lastPart
                );

            case 'irregular_dominion.received_hostile_spell':
                $sourceDominion = Dominion::with('realm')->find($data['sourceDominionId']);

                switch ($data['spellKey'])
                {
                    case 'clear_sight':
                        $resultString = 'Traces of enemy magic were detected our advisor\'s quarters.';
                        break;

                    case 'vision':
                        $resultString = 'Traces of enemy magic were detected our research facilities.';
                        break;

                    case 'revelation':
                        $resultString = 'Traces of enemy magic were detected in our spires.';
                        break;

                    case 'plague':
                        $resultString = 'A plague has befallen our people, slowing population growth.';
                        break;

                    case 'blight':
                        $resultString = 'A swarm of insects are eating our crops, slowing food production.';
                        break;

                    case 'great_flood':
                        $resultString = 'A great flood has damaged our docks, slowing boat production.';
                        break;

                    case 'earthquake':
                        $resultString = 'An earthquake has damaged our mines, slowing ore and gem production.';
                        break;

                    case 'disband_spies':
                        $resultString = "{$data['damageString']} have mysteriously deserted their posts.";
                        break;

                    case 'fireball':
                        $resultString = "A great fireball has crashed into our keep, burning {$data['damageString']}.";
                        break;

                    case 'lightning_bolt':
                        $resultString = "A great lightning bolt crashed into our improvements, destroying {$data['damageString']}.";
                        break;

                    case 'pyroclast':
                        $resultString = "Lava rains over our lands, burning {$data['damageString']}.";
                        break;

                    case 'frozen_shores':
                        $resultString = "An icy wind sweeps in and freezes our shores, preventing us from sending out boats.";
                        break;

                    # BEGIN Invasion Spells
                    case 'pestilence':
                        $resultString = "Our population has been afflicted by the Pestilence. Some of our people are dying.";
                        break;

                    case 'lesser_pestilence':
                        $resultString = "Our army has come in contact with a dominion suffering from Pestilence and now a Lesser Pestilence is spreading across our lands. Some of our people are dying.";
                        break;

                    case 'great_fever':
                        $resultString = "Our population has been afflicted by the Great Fever. Population has stopped growing and food and gold production are slowed.";
                        break;

                    case 'festinger_wounds':
                        $resultString = "Festering wounds are spreading across our people, increasing casualties and food consumption.";
                        break;

                    case 'purification':
                        $resultString = "{$data['damageString']} die from Tiranthael's Justice.";
                        break;

                    case 'annexation':
                        $resultString = "Our dominion has been annexed!";
                        break;

                    # END Invasion Spells

                    case 'curse_of_zidur':
                        $resultString = "A Curse of Zidur has been placed upon our lands.";
                        break;

                    case 'curse_of_kinthys':
                        $resultString = "A Curse of Kinthys has befallen us.";
                        break;

                    # Faction spells

                    case 'solar_rays':
                        $resultString = "{$data['damageString']} vanish under Solar Flares.";
                        break;

                    case 'enthralling':
                        $resultString = 'Dissent is spreading among our population and some feel enthralled by the Cult.';
                        break;

                    case 'treachery':
                        $resultString = 'Spies are accusing each other of treason.';
                        break;

                    case 'windchill':
                        $resultString = 'Magical icy winds weaken our wizards.';
                        break;

                    case 'mark_of_azk_hurum':
                        $resultString = 'Ask\'Hurum has marked us, weakening our defenses.';
                        break;

                    case 'elskas_blur':
                        $resultString = 'Our spies\' visions are blurred by Elska.';
                        break;

                    case 'voidspellmanatheft':
                        $resultString = "{$data['damageString']} has vanished into the void.";
                        break;

                    case 'silence':
                        $resultString = 'A magical state of silence is preventing us from performing sorcery.';
                        break;

                    default:
                        throw new LogicException("Received hostile spell notification for operation key {$data['spellKey']} not yet implemented");
                }

                if ($sourceDominion) {
                    return sprintf(
                        "{$resultString} Our wizards have determined that %s (#%s) was responsible!",
                        $sourceDominion->name,
                        $sourceDominion->realm->number
                    );
                }

                return $resultString;

            case 'irregular_dominion.received_friendly_spell':
                $sourceDominion = Dominion::with('realm')->find($data['sourceDominionId']);

                switch ($data['spellKey'])
                {
                    case 'iceshield':
                        $resultString = 'Iceshields protect our lands from fire and lightning.';
                        break;

                    case 'birdsong':
                        $resultString = 'Sylvan birdsong restores our morale.';
                        break;

                    case 'lightfoots':
                        $resultString = 'Halfling training strengthens our spies.';
                        break;

                    case 'blessing_of_azk_hurum':
                        $resultString = 'Ask\'Hurum has blessed us, giving us greater offensive power against enemies that have invaded our realm.';
                        break;

                    case 'mycelial_visions_friendly':
                        $resultString = 'Mycelial visions are increasing our mana production.';
                        break;

                    case 'protective_spores_friendly':
                        $resultString = 'Protective spores envelop our lands, reducing the damage from sorcery.';
                        break;

                    case 'verdant_fields_friendly':
                        $resultString = 'Myconid magic turns our fields verdant, increasing our food production.';
                        break;

                    default:
                        $resultString = 'Friendly wizards cast ' . $data['spellKey'] . ' on us.';
                        break;

                    #default:
                    #    throw new LogicException("Received friendly spell notification for operation key {$data['spellKey']} not yet implemented");
                }

                if ($sourceDominion)
                {
                    return sprintf(
                        "Thanks to wizards from %s, {$resultString}",
                        $sourceDominion->name,
                        $sourceDominion->realm->number
                    );
                }

                return $resultString;

            case 'irregular_dominion.repelled_hostile_spell':
                $sourceDominion = Dominion::with('realm')->findOrFail($data['sourceDominionId']);
                $spell = Spell::where('key', $data['spellKey'])->first();

                $lastPart = '!';
                if ($data['unitsKilled']) {
                    $lastPart = ", killing {$data['unitsKilled']}!";
                }

                return sprintf(
                    'Our wizards have repelled a %s spell attempt by %s (#%s)%s',
                    $spell->name,
                    $sourceDominion->name,
                    $sourceDominion->realm->number,
                    $lastPart
                );

            case 'irregular_dominion.reflected_hostile_spell':
                $sourceDominion = Dominion::with('realm')->findOrFail($data['sourceDominionId']);

                return sprintf(
                    'The energy mirror protecting our dominion has reflected a %s spell back at %s (#%s).',
                    $spell->name,
                    $sourceDominion->name,
                    $sourceDominion->realm->number,
                );

            case 'irregular_realm.enemy_realm_declared_war':
                $sourceRealm = Realm::findOrFail($data['sourceRealmId']);

                return sprintf(
                    '%s (#%s) declared war upon our realm!',
                    $sourceRealm->name,
                    $sourceRealm->number
                );

            case 'irregular_realm.declared_war_upon_enemy_realm':
                $targetRealm = Realm::findOrFail($data['targetRealmId']);

                return sprintf(
                    'Our realm declared war upon %s (#%s)!',
                    $targetRealm->name,
                    $targetRealm->number
                );

            // todo: other irregular etc

            default:
                throw new LogicException("Unknown WebNotification message for {$category}.{$type}");
        }
    }

    // todo: remove
    public function getIrregularTypes(): array
    {
        return [ // todo
            'Your dominion was invaded',
            // An army from Penrhyndeudraeth (# 14) invaded our lands, conquering 681 acres of land! We lost 237 draftees, 0 Slingers, 2647 Defenders, 643 Staff Masters and 3262 Master Thieves during the battle.
            'Your dominion repelled an invasion',
            // Forces from Night (# 42) invaded our lands, but our army drove them back! We lost 44 draftees, 0 Soldiers, 251 Miners, 199 Clerics and 0 Warriors during the battle.
            'Hostile spy op received',
            // 1 boats have sunk mysteriously while docked. | 145 draftees were killed while they slept in the barracks
            'Hostile spy op repelled',
            // Spies from Need more COWBELLT! (# 5) were discovered within the (draftee barracks | castle | vaults | docks/harbor?)! We executed 40 spies.
            'Hostile spell received',
            // ???
            'Hostile spell deflected',
            // Our wizards have repelled a Clear Sight spell attempt by And Thee Lord Taketh Away (# 21)!

            // Page: OP Center
            'Realmie performed info gathering spy op/spell',

            // Page: Town Crier
            'Realmie invaded another dominion',
            // Victorious on the battlefield, Priapus (# 16) conquered 64 land from Black Whirling (# 26).
            'Dominion failed to invade realmie',
            // Fellow dominion Jupiter (# 11) fended of an attack from Miss Piggy (# 31).
            'Realmie failed to invade another dominion',
            // Sadly, the forces of Starscream (# 31) were beaten back by Myself Yourself (# 44).
            'A dominion invaded realmie',
            // Battle Rain (# 29) invaded slow.internet.guy (# 16) and captured 440 land.
            'Our realm delared war upon another realm',
            // We have declared WAR on Rise of the Dragons (# 9)!
            'A realm has declared war upon us',
            // Golden Dragons (# 9) has declared WAR on us!
            'our wonder attacked',
            // Dirge (# 31) has attacked the Temple of the Damned!
            'our wonder destroyed',
            // The Temple of the Blessed has been destroyed and rebuilt by Realm #16!
            'death',
            // Cruzer (# 2) has been abandoned by its ruler.
        ];

        // after successful invasion:
        // Your army fights valiantly, and defeats the forces of Darth Vader, conquering 403 new acres of land! During the invasion, your troops also discovered 201 acres of land.

        // after failed invasion:
        // ???

        // after successful invasion, some racial effects:
        // In addition, your army converts some of the enemy casualties into 0 Skeletons, 0 Ghouls and 995 Progeny!
        // In addition, your Garous convert some of the enemy into 2781 werewolves to fight for our army!

        // Being scripted:
        // The game has automatically removed 43 acres of land because of apparent land farming of Elysia (# 9).

    }
}
