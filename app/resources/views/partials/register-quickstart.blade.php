<div class="col-md-{{ $bootstrapColWidth }}">
    <label class="btn btn-block">
        <div class="box">
            <div class="box-header with-border">
                <input type="radio" id="quickstart" name="quickstart" value="{{ $quickstart->id }}" required>&nbsp;<h4 class="box-title">{{ $quickstart->name }}</h4>
            </div>

            <div class="box-body table-responsive no-padding">
                <table class="table">
                    <colgroup>
                        <col width="50%">
                        <col width="50%">
                    </colgroup>
                    <tbody>
                        @if(!empty($quickstart->description))
                            <tr>
                                <td>
                                    <p>{{ $quickstart->description }}</p>
                                </td>
                            </tr>
                        @endif
                        <tr>
                            <th>Faction</th>
                            <th>Title</th>
                        </tr>
                        <tr>
                            <td class="text-left">{{ $quickstart->race->name }} <a href="{{ route('scribes.faction', $quickstart->race->name) }}" target="_blank"><i class="fas fa-book"></i></a></td>
                            <td class="text-left">{{ $quickstart->title->name }}</td>
                        </tr>

                        <tr>
                            <th>Offensive Power</th>
                            <th>Defensive power</th>
                        </tr>
                        <tr>
                            <td class="text-left">{{ number_format($quickstart->offensive_power) }} <em>(est.)</em></td>
                            <td class="text-left">{{ number_format($quickstart->defensive_power) }} <em>(est.)</em></td>
                        </tr>

                        <tr>
                            <th>Ticks Left</th>
                            <th>Land</th>
                        </tr>
                        <tr>
                            <td class="text-left">{{ number_format($quickstart->protection_ticks) }}</td>
                            <td class="text-left">{{ number_format(array_sum($quickstart->land)) }}</td>
                        </tr>

                        @if(!empty($quickstart->buildings))
                            <tr>
                                <th>Buildings</th>
                                <th>Resources</th>
                            </tr>
                            <tr>
                                <td class="text-left">
                                    <ul>
                                    @foreach($quickstart->buildings as $buildingKey => $amount)
                                        @if($amount > 0)
                                            @php
                                                $building = OpenDominion\Models\Building::where('key', $buildingKey)->first();
                                            @endphp
                                            <li>{{ $building->name }}: {{ number_format($amount) }}</li>
                                        @endif
                                    @endforeach
                                    </ul>
                                </td>
                                <td class="text-left">
                                    <ul>
                                    @foreach($quickstart->resources as $resourceKey => $amount)
                                        @if($amount > 0)
                                            @php
                                                $resource = OpenDominion\Models\Resource::where('key', $resourceKey)->first();
                                            @endphp
                                            <li>{{ $resource->name }}: {{ number_format($amount) }}</li>
                                        @endif
                                    @endforeach
                                    </ul>
                                </td>
                            </tr>
                        @endif

                        @if(!empty($quickstart->units))
                            <tr>
                                <th>Units</th>
                                <th>Full Details</th>
                            </tr>
                            <tr>
                                <td class="text-left">
                                    <ul>
                                    @foreach($quickstart->units as $unitKey => $amount)
                                        @if($amount > 0)
                                            @php
                                                if(in_array($unitKey, ['unit1','unit2','unit3','unit4','unit5','unit6','unit7','unit8','unit9','unit10']))
                                                {
                                                    $slot = (int)str_replace('unit','',$unitKey);

                                                    $unit = $quickstart->race->units->filter(function ($unit) use ($slot) {
                                                        return ($unit->slot === $slot);
                                                    })->first();
                                                    $unitName = $unit->name;
                                                }
                                                elseif(in_array($unitKey, ['spies','wizards','archmages']))
                                                {
                                                    $unitName = ucwords($unitKey);
                                                }
                                                else
                                                {
                                                    $unitName = $raceHelper->getDrafteesTerm($quickstart->race);
                                                }
                                            @endphp
                                            <li>{{ $unitName }}: {{ number_format($amount) }}</li>
                                        @endif
                                    @endforeach
                                    </ul>
                                </td>
                                <td class="text-left">
                                    <a href="{{ route('scribes.quickstart', $quickstart->id) }}" target="_blank"><i class="fas fa-book"></i> Scribes</a>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </label>
</div>