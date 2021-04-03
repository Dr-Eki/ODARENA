@extends('layouts.master')

@section('page-header', 'Military Mentor')

@section('content')
    @include('partials.dominion.mentor-selector')

    <div class="row">

        <div class="col-sm-12 col-md-8">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-sword"></i> Military</h3>
                </div>


                <div class="box-body">
                    <h4>General</h4>
                    <p>Training military units is important to keep your dominion safe from invaders and, if you so choose, to have an army strong enough to invade other dominions.</p>
                    <p>How you configure your military is dependent on your play style and which faction you are playing.</p>

                    <h4>Strategy for {{ $selectedDominion->race->name }}</h4>

                    @if($selectedDominion->race->name === 'Growth')
                        <p>In the beginning, train only Cysts until you reach a high food production. This can be between 20,000 and 40,000 Cysts.</p>
                        <p>Afterwards, train enough Abcesses to be able to spy and cast information spells on other dominions. A few thousand will suffice in most cases.</p>
                        <p>Then start training Ulcers until you have enough to start invading other dominions. Remember, you can also use your Abcesseses for offensive power.</p>
                        <p>Blisters are mostly useful if you need to defend from hostile spies and wizards. Otherwise refrain from training too many of them and focus on Cysts as defensive units, since they are twice as efficient.</p>
                        <p>Throughout the round, it is often good to overtrain Ulcers, so that you have more of them than you might need for invasions, as this gives you the flexibility of using some for defense while training potentially threatening OP.</p>

                    @elseif($selectedDominion->race->name === 'Simian')
                        <p>Gorillas and Chimpanzees become stronger the more you invade, so your early focus should be on getting to 10-20 Victories (successsful invasions against targets at least 75% your size).</p>
                        <p>Except for a few thousand Bonobos in the first day, train only Gorillas and Orangutans in the beginning. Since your focus is on earning victories, don't worry about being invaded. If you have a high ratio of forest, you will lose very few units.</p>
                        <p>If at some point you have a large population and need an extra push, train Chimpanzees. Otherwise, stay focused on Gorillas and Orangutans.</p>
                        <p>Simian spies are extra strong but wizards are weakened and Archmages cost extra. Unless you are in dire need of defensive wizards, it's not worth training lots of wizards and Archmages. Make sure you have enough for info ops on regular targets, but collaborate with your realm to get ops on for examples Dark Elves and Sylvans, rather than training a high WPA yourself.</p>

                    @elseif($selectedDominion->race->name === 'Marshling')
                        <p>Aside from spies and wizards, Marshlings can only train one unit: Spawns. You need 0.50 WPA to train Spawns, which means you need to have at least 1 wizard per 2 acres (or 1 Archmage per 4 acres).</p>
                        <p>Once you have that, your goal is simply to train Spawns. You obtain the higher level units by invading.</p>
                        <p>When successfully invading another dominion, Spawns morph into Slimes. Slimes morph into Goos. Goos morph into Oozes.</p>
                        <p>If a Slime dies, it reverts back to a Spawn. Goo reverts to Slime. When an Ooze dies, it's split in two and become two Spawns. And then the cycle repeats, with doubled potential.</p>

                    @elseif($selectedDominion->race->name === 'Void')
                        <p>Void relies entirely on effective mana production and the best way for Void to produce mana is by training Shadows and Visions.</p>
                        <p>In protection, start by training Visions until you have around 8,000 – 10,000. This gives enough mana production to transition into training Nightmares to begin invading other dominions.</p>
                        <p>As the round goes on, Visions are a better choice if you have more mana than you can spend. Shadows are good in small, targeted batches to increase your mana production.</p>
                        <p>Your offensive power should consist of only Nightmares, other than exceptional circumstances where Fallen may be needed to quickly boost your OP for a specific goal.</p>

                    @elseif($selectedDominion->race->name === 'Sacred Order')
                        <p>Keep a steady supply of Monks. They produce XP which helps you gain advancements faster. However, every Monk also means one peasant not earning you 2.7 gold each tick (plus bonuses), so don't keep too high of a supply.</p>
                        <p>Fanatics and Paladins are the only active military units that you can train. Make sure you maintain 10% Temples and Shrines at all times to max out their powers. A little extra is good so you don't drop under 10% if you gain new land.</p>
                        <p>You cannot train Martyrs. They can only be obtained by invading other dominions.</p>

                    @else
                        <p class="text-muted"><i>Not yet written. Want to contribute? Contact Dreki on <a href="{{ config('app.discord_invite_link') }}" target="_blank">Discord</a>.</i></p>
                    @endif

                </div>

            </div>
        </div>

        <div class="col-sm-12 col-md-4">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Your Current Military</h3>
                </div>


                <div class="box-body">
                <table class="table table-striped">
                    <colgroup>
                        <col width="150">
                        <col>
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Unit</th>
                            <th>Home</th>
                            <th>Away</th>
                            <th>Training</th>
                        </tr>
                    </thead>
                    @for ($slot = 1; $slot <= 4; $slot++)
                        @php
                            $unitType = 'unit'.$slot;
                        @endphp
                        <tr>
                            <td>{{ $unitHelper->getUnitName($unitType, $selectedDominion->race) }}</td>
                            <td>{{ number_format($selectedDominion->{'military_'.$unitType}) }}</td>
                            <td>{{number_format($queueService->getInvasionQueueTotalByResource($selectedDominion, "military_{$unitType}"))}}</td>
                            <td>{{number_format($queueService->getTrainingQueueTotalByResource($selectedDominion, "military_{$unitType}"))}}</td>
                        </tr>
                    @endfor
                    @if(!$selectedDominion->race->getPerkValue('cannot_train_spies'))
                    <tr>
                        <td>Spies</td>
                        <td>{{ number_format($selectedDominion->military_spies) }}</td>
                        <td>&mdash;</td>
                        <td>{{number_format($queueService->getTrainingQueueTotalByResource($selectedDominion, "military_spies"))}}</td>
                    </tr>
                    @endif
                    @if(!$selectedDominion->race->getPerkValue('cannot_train_wizards'))
                    <tr>
                        <td>Wizards</td>
                        <td>{{ number_format($selectedDominion->military_wizards) }}</td>
                        <td>&mdash;</td>
                        <td>{{number_format($queueService->getTrainingQueueTotalByResource($selectedDominion, "military_wizards"))}}</td>
                    </tr>
                    @endif
                    @if(!$selectedDominion->race->getPerkValue('cannot_train_archmages'))
                    <tr>
                        <td>Archmages</td>
                        <td>{{ number_format($selectedDominion->military_archmages) }}</td>
                        <td>&mdash;</td>
                        <td>{{number_format($queueService->getTrainingQueueTotalByResource($selectedDominion, "military_archmages"))}}</td>
                    </tr>
                    @endif
                    <tbody>
                    </tbody>
                </table>

                </div>

            </div>
        </div>

    </div>
@endsection
