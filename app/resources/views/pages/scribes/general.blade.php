@extends('layouts.topnav')
@section('title', "Scribes | General")

@section('content')
@include('partials.scribes.nav')

<div class="row">
    <div class="col-md-12 col-md-3">
        <div class="box">
            <ul style="list-style-type: none; margin:0; padding:0;">
                <li><a class="btn btn-block" href="#casualties">Casualties</a></li>
                <li><a class="btn btn-block" href="#community">Community</a></li>
                <li><a class="btn btn-block" href="#conversions">Conversions</a></li>
                <li><a class="btn btn-block" href="#crypt">Crypt</a></li>
                <li><a class="btn btn-block" href="#expeditions">Expeditions</a></li>
                <li><a class="btn btn-block" href="#governor">Governor</a></li>
                <li><a class="btn btn-block" href="#morale">Morale</a></li>
                <li><a class="btn btn-block" href="#prestige">Prestige</a></li>
                <li><a class="btn btn-block" href="#protectorship">Protectorship</a></li>
                <li><a class="btn btn-block" href="#psionics">Psionics</a></li>
                <li><a class="btn btn-block" href="#psionic_conversion">Psionic Conversion</a></li>
                <li><a class="btn btn-block" href="#rounds">Rounds</a></li>
                <li><a class="btn btn-block" href="#sabotage">Sabotage</a></li>
                <li><a class="btn btn-block" href="#sorcery">Sorcery</a></li>
                <li><a class="btn btn-block" href="#theft">Theft</a></li>
                <li><a class="btn btn-block" href="#ticks">Ticks</a></li>
            </ul>
        </div>
    </div>


    <div class="col-md-12 col-md-9">
        <div class="box">
            <div class="box-header with-border">
                <h2 class="box-title">Basics</h2>
            </div>
            <div class="box-body">
                <p>This page contains information about several aspects of the game. It can at times be technical, but it is also a useful resource for players to learn about the game.</p>
                <p>Other pages in the Scribes go into detail about specific aspects of the game. This page is focused on broader topics or game mechanics not elsewhere documented.</p>
                <p>Questions are best asked on Discord, where other players can help give clarity on the game and its inner workings.</p>
            </div>
        </div>
    </div>

</div>
<div class="row">
        
    <div class="col-sm-12 col-md-12">
        <a id="casualties"></a>
        <div class="box box-primary">
            <div class="box-header with-border">
                <h2 class="box-title">Casualties</h2>
            </div>
            <div class="box-body">
                <p>For each unit, casualties are calculated as follows:<p>
                <ol>
                <li>Is the unit immortal (including situations like “immortal on victory” and the dominion is victorious)? If yes, nothing else happens, casualties are zero. If no, continue.</li>
                <li>A base of 10% (on Offense) or 5% (on Defense) is set.</li>
                <li>Check for any of the following:
                    <ol>
                        <li>If attacker is overwhelmed, 2x the base.</li>
                        <li>If attacker is successful, divide the base by the OP/DP ratio (effectively only incurring casualties on as many units as were required to break).</li>
                        <li>For defender, multiply base by land ratio: <code>min(1, [Defender size]/[Attacker size])</code></li>
                        <li>If defender is successful, multiply the base by the OP/DP ratio (effectively only incurring casualties on as many units as were required to fend off).</li>
                        <li>If defender is unsuccessful, multiply the base by the OP/DP ratio: <code>min(1.50, [Attacker OP]/[Defender DP])</code>, effectively meaning you can incur up to 50% extra base casualties on defense this way.</li>
                    </ol>
                </li>
                <li>Check for <samp>only_dies_vs_raw_power</samp> perk:
                    <ol>
                        <li>Check if any of the enemy’s units have the required strength to kill the unit.</li>
                        <li>If an enemy unit does, calculate how much of the raw enemy military power came from this unit relative to the total raw military power of the enemy.</li>
                        <li>For example, if two units are strong enough but a third isn’t, a calculation could look like this:</li>
                        <li>Total raw DP: 24,000 DP</li>
                        <li>Unit 1: 8 DP each</li>
                        <li>Unit 2: 12 DP each</li>
                        <li>Unit 3: 4 DP each</li>
                        <li>Unit composition and DP calculation: <code>8*1,000+12*1,000+4*1,000=24,000</code></li>
                        <li>Minimum DP to kill: 6</li>
                        <li>The power ratio from killing units is then: <code>(8*1,000+12*1,000)/24,000=0.8333</code></li>
                        <li>The base is then multiplied by 0.8333 directly, <code>(5%*0.8333 or 10%*0.8333</code> before other casualty reduction perks are added).</li>
                    </ol>
                </li>
                <li>After <samp>only_dies_vs_raw_power</samp>, with the base lowered (or even zero), we continue with summing up casualty basic casualties perk multipliers (perks from title, faction, advancements, spells, improvements, buildings, deity).</li>
                <li>Add perks from this specific unit (i.e. unit casualty perks).</li>
                <li>Add perks from other units (i.e. <samp>reduces_casualties</samp> and <samp>increases_own_casualties</samp>).</li>
                <li>Combine all perks and multiply them with casualty perks from the enemy (<samp>increases_enemy_casualties</samp>).</li>
                <li>Apply multiplier on the base (the 10% or 5%).</li>
                </ol>
                <h4>Formula</h4>
                <p><code>([Base] * [Base Reduction From Only Dies vs. Raw Power Perk]) * ([Basic Perks] + [Unit Perks] + [Perks From Other Units]) * [Perk From Enemy Units]</code></p>
                <p>Casualty perks are clamped between -90% and +100%. The only exceptions are immortality (-100%), overwhelmed bounced (+100% on the base, which can be increased by a further +100%), and fixed casualties (always whatever the fixed number is, no perks apply).</p>
            </div>
        </div>
    </div>

    <div class="col-sm-12 col-md-12">
        <a id="community"></a>
        <div class="box box-primary">
            <div class="box-header with-border">
                <h2 class="box-title">Community</h2>
            </div>

            <div class="box-body">
                <p>Come join us on Discord!</p>
                <p style="padding: 0 20px;">
                    <a href="{{ config('app.discord_invite_link') }}" target="_blank">
                        <img src="{{ asset('assets/app/images/join-the-discord.png') }}" alt="Join the Discord" class="img-responsive" style="max-height: 80px;">
                    </a>
                </p>
            </div>
        </div>
    </div>
    
    <div class="col-sm-12 col-md-12">
        <a id="conversions"></a>
        <div class="box box-primary">
            <div class="box-header with-border">
                <h2 class="box-title">Conversions</h2>
            </div>

            <div class="box-body">
                <p>If multiple units are involved in the battle, the amount converted is calculated proportionately to how much each unit provided in raw OP (on offense) or raw DP (on defense).</p>
                <p>For example, if you have 200 units with 10 OP each and 100 unit with 5 OP, the 200 units with 10 OP each will convert 80% of the qualifying enemy casualties, and the unit with 50 OP will convert 20% of the qualifying enemy casualties.</p>
                <h4>Qualifying Casualties</h4>
                <p>Casualties by units which die (i.e. are not immortal) qualify for conversion, unless they have any of the following Unit Attributes or Unit Perks.</p>
                <div class="row">
                    <div class="col-sm-12 col-md-6">
                        <p><strong>Attributes</strong></p>
                        <ul>
                        @foreach($conversionHelper->getUnconvertibleAttributes() as $attribute)
                            <li>{{ ucwords($attribute) }}</li>
                        @endforeach
                        </ul>
                    </div>

                    <div class="col-sm-12 col-md-6">
                        <p><strong>Perks</strong></p>
                        <ul>
                        @foreach($conversionHelper->getUnconvertiblePerks() as $perk)
                            <li><samp>{{ ($perk) }}</samp></li>
                        @endforeach
                        </ul>
                    </div>
                </div>
            </div>

            <div class="box-footer">
                <p class="text-muted">See also <a href="#crypt">Crypt</a> and <a href="#psionic_conversion">Psionic Conversion</a></p>
            </div>
        </div>
    </div>
    
    <div class="col-sm-12 col-md-12">
        <a id="crypt"></a>
        <div class="box box-primary">
            <div class="box-header with-border">
                <h2 class="box-title">Crypt</h2>
            </div>

            <div class="box-body">
                <p>In Standard and Standard (Duration) rounds, the Imperial realm will gather some dead bodies and place them in the crypt, whence the <a href="{{ route('scribes.faction', 'undead') }}">Undead</a> can raise certain units.</p>
                <p>Only the bodies of units that would qualify for conversion are placed in the crypt. See <a href="#conversions">Conversions</a>.</p>

                <table class="table table-striped">
                    <colgroup>
                        <col width="30%">
                        <col width="35%">
                        <col width="35%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th></th>
                            <th>Offense</th>
                            <th>Defense</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Success</strong><br><small class="text-muted">When an Imperial dominion successfully defends or invades</small></td>
                            <td>
                                <ul>
                                    <li>50% of Attacker's bodies</li>
                                    <li>100% of Defender's bodies</li>
                                </ul>
                            </td>
                            <td>
                                <ul>
                                    <li>100% of Attacker's bodies</li>
                                    <li>100% of Defender's bodies</li>
                                </ul>
                            </td>
                        </tr>

                        <tr>
                            <td><strong>Failure</strong><br><small class="text-muted">When an Imperial dominion fails an invasion or is invaded</small></td>
                            <td>
                                <ul>
                                    <li>0% of Attacker's bodies</li>
                                    <li>0% of Defender's bodies</li>
                                </ul>
                            </td>
                            <td>
                                <ul>
                                    <li>None of Attacker's bodies</li>
                                    <li>50% of Defender's bodies</li>
                                </ul>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <p>Bodies in the crypt decay at a rate of 1% per tick.</p>

            </div>
        </div>
    </div>
    
    <div class="col-sm-12 col-md-12">
        <a id="expeditions"></a>
        <div class="box box-primary">
            <div class="box-header with-border">
                <h2 class="box-title">Expeditions</h2>
            </div>

            <div class="box-body">
                <p>Gain land by sending units out on expeditions and claim lands. The amount of land gained is calculated based on your current land size and the amount of Offensive Power you send.</p>
                <p>Offensive Power per 1 land required: <code>[Land Size] ** 1.25 / 5</code></p>
                <p>If you are devoted to or in the process of submitting to a deity, the amount of land gained is multiplied by <code>1 - [Deity Multiplier]</code>.
                <p>You also gain 25 XP per land discovered.</p>
                <p>Prestige gain: <code>([Land Discovered] / [Land Size]) * 200)</code></p>
            </div>
        </div>
    </div>
    
    <div class="col-sm-12 col-md-12">
        <a id="governor"></a>
        <div class="box box-primary">
            <div class="box-header with-border">
                <h2 class="box-title">Governor</h2>
            </div>

            <div class="box-body">
                <p>Each realm can have a dominion has governor. The governor is able to moderate the realm message board and write a message seen by all dominions on the Status page.</p>
                <p>In standard rounds, the governor is appointed by voting. Votes can only be changed once every 192 ticks.</p>
                <p>In deathmatch rounds, governorship is first claimed by the player who first hits another player. If the governor is successfully invaded by another player, the governorship is transferred to that player.</p> 
                <p>Governors gain 10% extra prestige. Successfully invading a governor gives 20% extra prestige for the attacker (if positive).</p>
            </div>
        </div>
    </div>
    
    <div class="col-sm-12 col-md-12">
        <a id="morale"></a>
        <div class="box box-primary">
            <div class="box-header with-border">
                <h2 class="box-title">Morale</h2>
            </div>

            <div class="box-body">
                <h4>Multiplier</h4>
                <p>Offensive Power, Military Power, Wizard Ratio, Spy Ratio, and Production are modified by the Morale Multiplier.</p>
                <p>Morale Multiplier: <code>0.9 + [Morale]/1000</code>. 120% morale yields a Morale Multiplier of: <code>0.9+120/1000=1.02</code>. The already modified power, ratio, or production is multiplied by this multiplier.</p>
                <p>10,000 raw DP with 25% DP mod and 120% morale yields: <code>10000 * (1+0.25) * (0.9+120/1000)=12,750</code>.</p>

                <h4>Gains and Losses</h4>
                <p>Morale is gained and lost primarily through invasions. On invasions, attacker's morale change is calculated as follows:</p>
                <ul>
                    <li>Where the land ratio is less than 60%: <code>-15 - (60 - [Land Ratio])</code></li>
                    <li>Land ratio greater than 60% and less than 75%: No morale change.</li>
                    <li>75% and up: <code>10 * ([Land Ratio] / 75) * (1 + [Land Ratio] / 100)</code></li>
                </ul>
                <p>Defender's morale change is <code>[Attacker Morale Change]*-1</code> for under 60% land ratio and <code>[Attacker Morale Change]*-0.60</code> for other. This calculation is done before attacker's morale modifiers (i.e. perks that increases attacker's morale gains) are applied.</p>

                <h4>Morale Perks</h4>
                <p>Morale is calculated in two steps: Base Morale and Morale Multipliers.</p>
                <p>When a perk increases base morale, it’s an additive bonus added to the 100% standard. For example, one Legate gives <code>100% + 5% = 105%</code> base morale.</p>
                <p>Multipliers are for example Taverns. 5% bonus from Taverns and no base morale modifiers yields <code>(100% + 0%) * (1.05) = 105%</code> morale.</p>
                <p>A dominion with 20% bonus from Taverns and 10 units that provide 0.50% morale would have <code>(100% + 10*0.50%) * (1.20) = 126%</code> morale.</p>

            </div>
        </div>
    </div>
    
    <div class="col-sm-12 col-md-12">
        <a id="psionics"></a>
        <div class="box box-primary">
            <div class="box-header with-border">
                <h2 class="box-title">Psionics</h2>
            </div>

            <div class="box-body">
                <p>Psionic strength is a measurement of the overall mental fortitude of a dominion. It is calculated as <code>([Base] * (1 + [Psionic Strength Perks] + [Psionic strength from units]) / [Population]</code>.</p>
                <ul>
                    <li>Base is 1.00 for everyone except Cult, whose base is 1.50.</li>
                    <li>There are currently no psionic strength perks.</li>
                    <li>Psionic strength from units is calculated for each unit as <code>([Amount of unit] * [Psionic strength from unit's attributes]) / [Population]</code>.</li>
                    <li>Psionic strength from attributes is as follows:
                        <ul>
                            <li>Mindless: -0.5</li>
                            <li>Intelligent: 0.5</li>
                            <li>Wise: 2</li>
                            <li>Psychic: 4</li>
                            <li>Others/default: 0</li>
                        </ul>
                    </li>
                    <li>For example, an Adept has the value 2.5 from Living (0) + Sentient (0) + Intelligent (0.5) + Wise (2) = 2.50.</li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="col-sm-12 col-md-12">
        <a id="psionic_conversion"></a>
        <div class="box box-primary">
            <div class="box-header with-border">
                <h2 class="box-title">Psionic Conversion</h2>
            </div>

            <div class="box-body">
                <p>Psionic conversion is conversion of units through a combination of persuasion and tricks of the mind. It only works when the <a href="{{ route('scribes.faction', 'cult') }}">Cult</a> is successful (none on bounces or getting hit) and only on conscious, thinking enemy beings which survive the invasion and are not immortal.</p>
                <p>It is based on regular conversion, but whereas regular conversion cannot convert units that have the attributes <samp>Aspect</samp> or </samp>Fused</samp>, psionic conversion can convert such units but cannot convert units with the attributes <samp>Wise</samp> or <samp>Mindless</samp>.</p>
                <p>Psionic conversion is calculated as follows:</p>
                <ul>
                <li>Base ratio: 1% on offense (when Cult is invading you), 0.50% on defense (when you invade Cult)</li>
                <li>Base ratio is multiplied by the OP:DP Ratio of the invasion (max 1.25).</li>
                <li>A multiplier is calculated as: <code>0 + ([Cult Psionic Strength] / [Enemy Psionic Strength]) + [Enemy casualty reduction perk from Cult units]</code>. Cult and Enemy Psionic Strenghts are capped at 2 in this calculation.</li>
                <li>Conversion ratio is <code>[Base Ratio] * [Multiplier]</code>.</li>
                <li>The conversion ratio is applied to all eligible surviving enemy units and, on offense, peasants.</li>
                <li>Units psionically converted are reduced by applicable casualty perks (you can never lose more units to psionic conversions than were killed).</li>
                <li>The converted units are lost for the enemy and all become Thralls for the Cult.</li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="col-sm-12 col-md-12">
        <a id="prestige"></a>
        <div class="box box-primary">
            <div class="box-header with-border">
                <h2 class="box-title">Prestige</h2>
            </div>

            <div class="box-body">
                <h4>Multiplier</h4>
                <p>Offensive Power, Food Production, and Population are modified by the Prestige Multiplier.</p>
                <p>Prestige Multiplier: <code>[Prestige]/10000</code>.</p>
                <p>For Offensive Power, the Prestige Multiplier is additive. For Food Production and Population, it's multiplicative.</p> 

                <h4>Gains and Losses</h4>
                <p>Everyone starts with 600 prestige. Prestige is gained and lost primarily through invasions. On invasions, attacker's prestige change is calculated as follows:</p>
                <table class="table table-striped">
                    <colgroup>
                        <col width="30%">
                        <col width="35%">
                        <col width="35%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th></th>
                            <th>Attacker</th>
                            <th>Defender</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Victory</strong><br><small class="text-muted">Successful invasion 75% or greater</small></td>
                            <td><code>60 * [Land Ratio] * [Net Victory Ratio Multiplier]</code></td>
                            <td><code>-20 * [Land Ratio]</code></td>
                        </tr>

                        <tr>
                            <td><strong>Bottomfeed over 60%</strong><br><small class="text-muted">Successful invasion 60%—75%</small></td>
                            <td><em class="text-muted">No change</em></td>
                            <td><em class="text-muted">No change</em></td>
                        </tr>

                        <tr>
                            <td><strong>Bottomfeed under 60%</strong><br><small class="text-muted">Successful invasion 40%–60%</small></td>
                            <td><code>-20</code></td>
                            <td><em class="text-muted">No change</em></td>
                        </tr>

                        <tr>
                            <td><strong>Bounce</strong><br><small class="text-muted">Unsuccessful invasion, less than 80% of required OP</small></td>
                            <td><code>-20</code></td>
                            <td><em class="text-muted">No change</em></td>
                        </tr>

                        <tr>
                            <td><strong>Raze</strong><br><small class="text-muted">Unsuccessful invasion, land ratio at least 75%, and at least 80% of required OP</small></td>
                            <td><em class="text-muted">No change</em></td>
                            <td><code>+10</code></td>
                        </tr>
                    </tbody>
                </table>

                <h4>Prestige Interest</h4>
                <p>Prestige is also increased passively through Prestige Interest:</p>
                <p><code>[Prestige] * (max(0, [Dominion Net Victories]) / 40000)</code></p>
                <p>Decimals of prestige are calculated but not used when calculating any perks originating from prestige. If you have 600.99 prestige, you will have 600 prestige for the purposes of perks.</p>
            </div>
        </div>
    </div>
    
    <div class="col-sm-12 col-md-12">
        <a id="protectorship"></a>
        <div class="box box-primary">
            <div class="box-header with-border">
                <h2 class="box-title">Protectorship</h2>
            </div>

            <div class="box-body">
                <p>Artillery dominions no longer have any DP of their own. Instead, a protectorship is established whereby another dominion act as as the protector of the Artillery. The protector is responsible for defending the Artillery dominion over which it has protectorship.</p>
                <p>Only Black Orc, Dark Elf, Orc, and Reptilians can be protectors.</p>
                <p>Potential protectors can submit protectorship offers to any unprotected Artillery dominions from the Government page. It is also possible to rescind offers. Artillery dominions can accept or decline offers from the Government page. Accepting an offer automatically deletes any other offers. It is not possible to cancel a protectorship once it has been established. Protectorships are permanent.</p>
                <p>Each Artillery dominion must have a protector in order to leave magical protection.</p>
                <p>The defensive power of Artillery dominions is the defensive power of its protector. Artillery does not build any defensive power by itself. The protector is wholly and solely responsible for the defense of the Artillery.</p>
                <p>An invasion against Artillery goes up against the military of the defender but, if successful, takes land from the Artillery and both the protector and the Artillery suffer prestige and morale losses. If the invasion is fended off, the protector gains the prestige and both the protector and the Artillery gain morale.</p>
                <p>When the protector is victorious in battle and gets a morale boost, the Artillery gains 25% of that morale boost.</p>
            </div>
        </div>
    </div>
    
    <div class="col-sm-12 col-md-12">
        <a id="rounds"></a>
        <div class="box box-primary">
            <div class="box-header with-border">
                <h2 class="box-title">Rounds</h2>
            </div>

            <div class="box-body">
                <p>There are four round types:</p>
                <table class="table table-striped">
                    <colgroup>
                        <col width="20%">
                        <col width="30%">
                        <col width="30%">
                        <col width="20%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Mode</th>
                            <th>Realms</th>
                            <th>Goal</th>
                            <th>Duration</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><i class="fas fa-users fa-fw text-green"></i> Standard</td>
                            <td>Four realms: one per alignment.</td>
                            <td>The first dominion to a certain land size triggers a 48-tick countdown.</td>
                            <td>Indefinite</td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-users fa-fw text-green"></i> Standard (Duration)</td>
                            <td>Four realms: one per alignment.</td>
                            <td>The round lasts a certain number of ticks.</td>
                            <td>Fixed</td>
                        </tr>

                        <tr>
                            <td><i class="ra ra-daggers ra-fw text-red"></i> Deathmatch</td>
                            <td>Two realms: Barbarians and Players. In-realm invasions and other operations are enabled.</td>
                            <td>The first dominion to a certain land size triggers a 48-tick countdown.</td>
                            <td>Indefinite</td>
                        </tr>
                        <tr>
                            <td><i class="ra ra-daggers ra-fw text-red"></i> Deathmatch (Duration)</td>
                            <td>Two realms: Barbarians and Players. In-realm invasions and other operations are enabled.</td>
                            <td>The round lasts a certain number of ticks.</td>
                            <td>Fixed</td>
                        </tr>
                    </tbody>
                </table>
                <p>In Standard and Standard (Duration) rounds, multis are allowed as long as they are all played with equal efforts and are all of the same alignment (same realm).</p>
                <p>In Deathmatch and Deathmatch (Duration) rounds, multis are not allowed.</p>
                <p>Unless otherwise announced, even numbered rounds start at 16:00 UTC and odd numbered rounds start at 04:00 UTC.</p>
            </div>
        </div>
    </div>

    <div class="col-sm-12 col-md-12">
        <a id="sabotage"></a>
        <div class="box box-primary">
            <div class="box-header with-border">
                <h2 class="box-title">Sabotage</h2>
            </div>

            <div class="box-body">
                <p>Damage is based on a Base Damage defined for each possible <a href="{{ route('scribes.sabotage') }}">Sabotage</a> operation, which can be be increased and decreased through perks.</p>
                <p>Damage is calculated in the following manner:</p>
                <ol>
                    <li>Ratio Multiplier: <code>1 + (([Saboteur SPA] - [Target SPA]) / [Saboteur SPA])</code>, min 0, max 1.</li>
                    <li>Target Multiplier: the sum of the target’s sabotage damage suffered perks (for example spells or buildings which lower sabotage damage).</li>
                    <li>Saboteur Multiplier: the sum of the saboteur’s sabotage damage dealt perks (for example spells and improvements which increase sabotage damage).</li>
                </ol>
                <p>If base damage is 2, Ratio Multiplier is 1, Target Multiplier is 0.25, Saboteur Multiplier is 1, and you send 5,000 units, damage dealt is: <code>2 * 1 * 0.25 * 1 * 5,000 = 2,500</code>.</p>
            </div>
        </div>
    </div>

    <div class="col-sm-12 col-md-12">
        <a id="sorcery"></a>
        <div class="box box-primary">
            <div class="box-header with-border">
                <h2 class="box-title">Sorcery</h2>
            </div>

            <div class="box-body">
                <ol>
                    <li>Select a target (normal range conditions).</li>
                    <li>Select spell to cast.</li>
                    <li>Select how much Wizard Strength you wish to spend on this sorcery.</li>
                    <li>Cast the spell.</li>
                </ol>
                <p>Mana cost is determined by a base mana cost (which, like for traditional spell casting is a value multiplied by current land size), which is multiplied by the wizard strength.</p>
                <p>For example, if you are 2,000 acres and a spell has a cost multiplier of 0.1x, it will cost 200 mana per 1% Wizard Strength. So if you spend 100% Wizard Strength on that spell, it will cost you <code>2,000 * 0.1 * 100 = 20,000</code> mana.</p>
                <p>Spell damage is calculated as <code>[Spell Base Damage] * [Sorcery Spell Damage Multiplier]</code>:</p>
                <ul>
                    <li>Each spell is assigned Base Damage.</li>
                    <li>Sorcery Spell Damage Multiplier is calculated as <code>((1 + [Sorcery Damage Dealt Multiplier]) * [Wizard Strength Multiplier] * [Wizard Ratio Multiplier])</code></li>
                    <li>Sorcery Damage Dealt multiplier is a sum of various perks a dominion can amass.</li>
                    <li>Wizard strength multiplier is <code>max([Wizard Strength], ([Wizard Strength] * (exp([Wizard Strength]/120)-1))</code></li>
                    <li>Wizard Ratio multiplier is <code>1 + (([Caster WPA] - [Target WPA]) / [Caster WPA])</code>, min 0, max 1.5.</li>
                    <li>For auras (passive spells, with a lingering effect), the base damage is the duration of the spell, meaning you can make for example Plague last longer by having stronger wizards or spending extra WS. The multiplier is divided by 20 and spell max duration is capped at 96 ticks.</li>
                </ul>
                <p>You need a minimum of 4% Wizard Strength and 0.10 WPA (on offense) to perform sorcery.</p>
            </div>
        </div>
    </div>

    <div class="col-sm-12 col-md-12">
        <a id="theft"></a>
        <div class="box box-primary">
            <div class="box-header with-border">
                <h2 class="box-title">Theft</h2>
            </div>

            <div class="box-body">
                <ol>
                    <li>Select a target (normal range conditions).</li>
                    <li>Select resource to steal.</li>
                    <li>Select how many spies and/or spy units to send.</li>
                    <li>Steal!</li>
                </ol>
                <p>The number of spies you select correspond to a value of 0% to 100% of your available spies, which is also the amount of spy strength that is drained when stealing. Send half your spies, and it costs 50% spy strength. (This number is rounded up, so sending 1 unit even if you have 100,000 spy units costs 1% Spy Strength).</p>
                <p>Your Spy Strength limits how many of your current spies and spy units you can send. If you have 25% Spy Strength, you can only send out 25% of your total spies and spy units.</p>
                <p>The spies arrive immediately at the target and try to steal as much of the resource as possible.<p>
                <p>Amount stolen is based on the max carry per spy:</p>
                <p><code>[Amount Stolen] = ([Number of Spy Units] * [Mod Max Carry Per Spy]) * MAX(MIN((1-([Target SPA] / [Thief SPA])*0.5)),1),0)</code></p>
                <p>The Amount Stolen is reduced by <code>min([Target SPA], 4) * -0.25</code>, which means that 1 SPA is 25% reduction and 4 SPA is 100% reduction.</p>
                <p>Spy units take a base of 1% casualties:</p>
                <p><code>[Units Killed] = 0.01 * (1 + ([Target SPA] / [Thief SPA]) * [Spy Losses Perk]</code></p>
                <p>If <code>[Target SPA] / [Thief SPA]</code> is equal to or less than 0.25, there is a one in <code>(1/([Target SPA] / [Thief SPA])</code> chance that no spy units are killed. — Basically, if you have at least 4x the SPA of the target, there’s an increasing chance your spies go unharmed.</p>
                <p>It takes 6 ticks for units to return home with the stolen resources.</p>
                <p>You need at least 20% Spy Strength to send out spies (but you can send less than 20% Spy Strength's worth of spies).</p>
            </div>
        </div>
    </div>
    
    <div class="col-sm-12 col-md-12">
        <a id="ticks"></a>
        <div class="box box-primary">
            <div class="box-header with-border">
                <h2 class="box-title">Ticks</h2>
            </div>

            <div class="box-body">
                <p>The game ticks four times per hour, every 15 minutes at xx:00, xx:15, xx:30, xx:45.</p>
                <p>When the round begins, there is an immediate tick.</p>
                <p>You start with 96 protection ticks, during which you can manually tick yourself forward at your own leisure.</p>
            </div>
        </div>
    </div>

</div>

</div>
@endsection
