@extends('layouts.topnav')

@section('content')
    <div class="box box-primary">
        <div class="box-header with-border">
            <h1 class="box-title"><i class="ra ra-angel-wings"></i> Valhalla for round {{ number_format($round->number) }}: {{ $round->name }}</h1>
        </div>
        <div class="box-body">

            <div class="row">
                <div class="col-md-12 text-center">
                    <h3>Overall</h3>
                </div>
            </div>
            <div class="row form-group">
                <div class="col-sm-6 text-center">
                    <h4>Largest</h4>
                    <a href="{{ route('valhalla.round.type', [$round, 'largest-dominions']) }}">The Largest Dominions</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'largest-realms']) }}">The Largest Realms</a><br>
                </div>
                <div class="col-sm-6 text-center">
                    <h4>Strongest</h4>
                    <a href="{{ route('valhalla.round.type', [$round, 'strongest-dominions']) }}">The Strongest Dominions</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'strongest-realms']) }}">The Strongest Realms</a><br>
                </div>
            </div>

            </div>
            <div class="row form-group">
                <div class="col-sm-12 text-center">
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-land-conquered']) }}">Largest Attacking Dominions<br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-attacking-success']) }}">Most Victorious Dominions</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-land-explored']) }}">Largest Exploring Dominions<br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-prestige']) }}">Most Prestigious Dominions</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-units-killed']) }}">Most Units Killed</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-units-converted']) }}">Most Units Converted</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-land-lost']) }}">Most Land Lost</a><br>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 text-center">
                    <h3>Espionage and Magic</h3>
                </div>
            </div>
            <div class="row form-group">
                <div class="col-sm-6 text-center">
                    <h4>Spies</h4>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-espionage-success']) }}">Most Successful Spies</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-gold-stolen']) }}">Top Gold Thieves</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-food-stolen']) }}">Top Food Thieves</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-lumber-stolen']) }}">Top Lumber Thieves</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-mana-stolen']) }}">Top Mana Thieves</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-ore-stolen']) }}">Top Ore Thieves</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-gems-stolen']) }}">Top Gem Thieves</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-top-saboteurs']) }}">Top Saboteurs</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-top-magical-assassins']) }}">Top Magical Assassins</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-top-military-assassins']) }}">Top Military Assassins</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-top-snare-setters']) }}">Top Snare Setters</a><br>
                    <!-- Top Demoralizers -->
                </div>
                <div class="col-sm-6 text-center">
                    <h4>Wizards</h4>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-spell-success']) }}">Most Successful Wizards</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-masters-of-fire']) }}">Masters of Fire</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-masters-of-plague']) }}">Masters of Plague</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-masters-of-swarm']) }}">Masters of Swarm</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-masters-of-lightning']) }}">Masters of Lightning</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-masters-of-water']) }}">Masters of Water</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-masters-of-earth']) }}">Masters of Earth</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-top-spy-disbanders']) }}">Top Spy Disbanders</a><br>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 text-center">
                    <h3>Resources</h3>
                </div>
            </div>
            <div class="row form-group text-center">
                <div class="col-sm-4 text-center">
                    <h4>Gold</h4>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-gold-spent-training']) }}">Most Spent Training</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-gold-spent-building']) }}">Most Spent Building</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-gold-spent-rezoning']) }}">Most Spent Rezoning</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-gold-spent-exploring']) }}">Most Spent Exploring</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-gold-spent-improving']) }}">Most Spent Improving</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-gold-plundered']) }}">Most Plundered</a><br>
                    {{--<a href="{{ route('valhalla.round.type', [$round, 'stat-total-gold-salvaged']) }}">Most Salvaged</a><br>--}}
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-gold-stolen']) }}">Most Stolen</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-gold-sold']) }}">Most Sold</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-gold-bought']) }}">Most Bought</a><br>
                </div>
                <div class="col-sm-4 text-center">
                    <h4>Food</h4>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-food-spent-training']) }}">Most Spent Training</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-food-spent-building']) }}">Most Spent Building</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-food-spent-rezoning']) }}">Most Spent Rezoning</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-food-spent-exploring']) }}">Most Spent Exploring</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-food-spent-improving']) }}">Most Spent Improving</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-food-plundered']) }}">Most Plundered</a><br>
                    {{--<a href="{{ route('valhalla.round.type', [$round, 'stat-total-food-salvaged']) }}">Most Salvaged</a><br>--}}
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-food-decayed']) }}">Most Decayed</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-food-consumed']) }}">Most Consumed</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-food-stolen']) }}">Most Stolen</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-food-sold']) }}">Most Sold</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-food-bought']) }}">Most Bought</a><br>
                </div>
                <div class="col-sm-4 text-center">
                    <h4>Ore</h4>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-ore-spent-training']) }}">Most Spent Training</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-ore-spent-building']) }}">Most Spent Building</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-ore-spent-rezoning']) }}">Most Spent Rezoning</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-ore-spent-exploring']) }}">Most Spent Exploring</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-ore-spent-improving']) }}">Most Spent Improving</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-ore-plundered']) }}">Most Plundered</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-ore-salvaged']) }}">Most Salvaged</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-ore-stolen']) }}">Most Stolen</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-ore-sold']) }}">Most Sold</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-ore-bought']) }}">Most Bought</a><br>
                </div>


                </div>
                <div class="row form-group text-center">

                <div class="col-sm-4 text-center">
                    <h4>Lumber</h4>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-lumber-spent-training']) }}">Most Spent Training</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-lumber-spent-building']) }}">Most Spent Building</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-lumber-spent-rezoning']) }}">Most Spent Rezoning</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-lumber-spent-exploring']) }}">Most Spent Exploring</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-lumber-spent-improving']) }}">Most Spent Improving</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-lumber-plundered']) }}">Most Plundered</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-lumber-salvaged']) }}">Most Salvaged</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-lumber-rotted']) }}">Most Rotted</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-lumber-stolen']) }}">Most Stolen</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-lumber-sold']) }}">Most Sold</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-lumber-bought']) }}">Most Bought</a><br>
                </div>
                <div class="col-sm-4 text-center">
                    <h4>Gems</h4>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-gem-spent-training']) }}">Most Spent Training</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-gem-spent-building']) }}">Most Spent Building</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-gem-spent-rezoning']) }}">Most Spent Rezoning</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-gem-spent-exploring']) }}">Most Spent Exploring</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-gem-spent-improving']) }}">Most Spent Improving</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-gem-plundered']) }}">Most Plundered</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-gem-salvaged']) }}">Most Salvaged</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-gems-stolen']) }}">Most Stolen</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-gems-sold']) }}">Most Sold</a><br>
                </div>
                <div class="col-sm-4 text-center">
                    <h4>Mana</h4>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-mana-spent-training']) }}">Most Spent Training</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-mana-spent-building']) }}">Most Spent Building</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-mana-spent-rezoning']) }}">Most Spent Rezoning</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-mana-spent-exploring']) }}">Most Spent Exploring</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-mana-spent-improving']) }}">Most Spent Improving</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-mana-plundered']) }}">Most Plundered</a><br>
                    {{--<a href="{{ route('valhalla.round.type', [$round, 'stat-total-mana-salvaged']) }}">Most Salvaged</a><br>--}}
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-mana-drained']) }}">Most Drained</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-mana-cast']) }}">Most Cast</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-mana-stolen']) }}">Most Stolen</a><br>
                </div>

            </div>
            <div class="row form-group text-center">
                <div class="col-sm-6 text-center">
                    <h4>Souls</h4>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-soul-spent-training']) }}">Most Spent Training</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-soul-spent-improving']) }}">Most Spent Improving</a><br>
                </div>

                <div class="col-sm-6 text-center">
                    <h4>Legendary Champions</h4>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-champion-spent-training']) }}">Most Spent Training</a><br>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 text-center">
                    <h3>Factions</h3>
                </div>
            </div>
            <div class="row form-group">
                <div class="col-sm-6 text-center">
                    <h4>Strongest</h4>
                    @foreach ($races as $race)
                        @php $raceSlug = 'strongest-' . str_slug($race); @endphp
                        <a href="{{ route('valhalla.round.type', [$round, $raceSlug]) }}">The Strongest {{ str_plural($race) }}</a><br>
                    @endforeach
                </div>
                <div class="col-sm-6 text-center">
                    <h4>Largest</h4>
                    @foreach ($races as $race)
                        @php $raceSlug = 'largest-' . str_slug($race); @endphp
                        <a href="{{ route('valhalla.round.type', [$round, $raceSlug]) }}">The Largest {{ str_plural($race) }}</a><br>
                    @endforeach
                </div>
            </div>


            <div class="row">
                <div class="col-md-12 text-center">
                    <h3>Units</h3>
                </div>
            </div>
            <div class="row form-group">
                <div class="col-sm-3 text-center">
                    <h4>Unit 1</h4>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-unit1-trained']) }}">Most Trained</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-unit1-lost']) }}">Most Lost</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-unit1-spent-training']) }}">Most Spent Training</a><br>
                </div>
                <div class="col-sm-3 text-center">
                    <h4>Unit 2</h4>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-unit2-trained']) }}">Most Trained</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-unit2-lost']) }}">Most Lost</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-unit2-spent-training']) }}">Most Spent Training</a><br>
                </div>
                <div class="col-sm-3 text-center">
                    <h4>Unit 3</h4>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-unit3-trained']) }}">Most Trained</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-unit3-lost']) }}">Most Lost</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-unit3-spent-training']) }}">Most Spent Training</a><br>
                </div>
                <div class="col-sm-3 text-center">
                    <h4>Unit 4</h4>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-unit4-trained']) }}">Most Trained</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-unit4-lost']) }}">Most Lost</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-unit4-spent-training']) }}">Most Spent Training</a><br>
                </div>
            </div>

            <div class="row form-group">
                <div class="col-sm-4 text-center">
                    <h4>Spies</h4>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-spies-trained']) }}">Most Trained</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-spies-lost']) }}">Most Lost</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-spies-spent-training']) }}">Most Spent Training</a><br>
                </div>
                <div class="col-sm-4 text-center">
                    <h4>Wizards</h4>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-wizards-trained']) }}">Most Trained</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-wizards-lost']) }}">Most Lost</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-wizards-spent-training']) }}">Most Spent Training</a><br>
                </div>
                <div class="col-sm-4 text-center">
                    <h4>Archmages</h4>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-archmages-trained']) }}">Most Trained</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-archmages-lost']) }}">Most Lost</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-archmages-spent-training']) }}">Most Spent Training</a><br>
                </div>
            </div>


        </div>
    </div>
@endsection
