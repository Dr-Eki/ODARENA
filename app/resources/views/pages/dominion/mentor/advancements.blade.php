@extends('layouts.master')

@section('page-header', 'Advancements Mentor')

@section('content')
    @include('partials.dominion.mentor-selector')
    <div class="row">

        <div class="col-sm-12 col-md-8">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-help"></i> Mentor: Advancements</h3>
                </div>


                <div class="box-body">
                    <h4>General</h4>
                    <p>Advancements give your dominion various boosts in different ways, such as increased production, better military, or stronger spies and wizards.</p>

                    <p>You can level up advancements by earning enough experience points (XP). XP is primarily earned based on your prestige. Each tick, you get  You earn XP by invading other dominions, exploring new lands, and every tick from your prestige.</p>

                    <p>Since your prestige is {{ $selectedDominion->prestige }} and your XP production multiplier (if any) is {{ number_format(($productionCalculator->getTechProductionMultiplier($selectedDominion)-1)*100,2) }}%, you currently produce {{ number_format($productionCalculator->getTechProduction($selectedDominion)) }} XP per tick.</p>

                    <h4>Strategy</h4>

                    <p>Focus on advancements that directly and immediately benefit you, but plan for the future.</p>
                    <p>In the beginning, Anvils is often the best advancement to begin with to reduce training costs. However, remember that max unit cost reduction is 50% and if you already have max Smithies, you only need to level up Anvils to level 4. But if you go beyond level 4, you can reduce Smithies and use the now free land for other buildings.</p>

                    <p>Honour is also good to level up early. The earlier you take it, the more invasions benefit from the increased prestige gains.</p>

                    <p>If your strategy involves a lot of Improvements, levelling up Infrastructure can make a big difference. Combine this with the advancement for the resource you are using for Improvements, for example Gems (Drills, Gemcutting) or Lumber (Mills).</p>

                    <p>Be mindful of two potential pitfalls: overdiversification and overspecialiation.</p>
                    <p>If you spread your XP across to many lower-level advancements, you may not see much of an impact. On the other hand, going all the way to level 10 is a big investment and the higher level advancements are less impactful.</p>
                    <p>As a rule of thumb, only level up advancements which you know will benefit you and are worth going to level 6. Go to level 8 if it's an advancement that benefits you a lot. Leave levels 9 and 10 for extremely valuable advancements.</p>
                </div>

            </div>
        </div>

        <div class="col-sm-12 col-md-4">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-flask"></i> Your Current Advancements</h3>
                </div>


                <div class="box-body">
                <table class="table table-striped">
                    <colgroup>
                        <col width="150">
                        <col>
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Tech</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($techs as $tech)
                            @php
                                $tech = OpenDominion\Models\Tech::where('key', $tech['key'])->firstOrFail();
                            @endphp
                            <tr>
                                <td>{{ $tech['name'] }} Level {{ $tech['level'] }}</td>
                                <td>{{ $techHelper->getTechDescription($tech) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                </div>

            </div>
        </div>

    </div>
@endsection
