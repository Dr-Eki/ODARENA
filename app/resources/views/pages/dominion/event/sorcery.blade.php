@extends('layouts.master')
@section('title', 'Sorcery')

@section('content')
@php
    $boxColor = 'success';
    $spell = OpenDominion\Models\Spell::where('key', $event->data['spell_key'])->first();

    if($event->source->realm->id == $selectedDominion->realm->id)
    {
        $class = 'green';
    }
    else
    {
        $class = 'red';
    }
@endphp
@if($selectedDominion->realm->id !== $event->source->realm->id and $selectedDominion->realm->id !== $event->target->realm->id)
    <div class="row">
        <div class="col-sm-6 col-md-4 col-md-offset-4">
            <div class="box box-{{ $boxColor }}">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fas fa-hat-wizard"></i> Sorcery
                    </h3>
                </div>
                <div class="box-bod no-padding">
                    You cannot view this event.
                </div>
            </div>
        </div>
    </div>
@else
    <div class="row">
        <div class="col-sm-6 col-md-4 col-md-offset-4">
            <div class="box box-{{ $boxColor }}">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fas fa-hat-wizard"></i> Sorcery
                    </h3>
                </div>
                <div class="box-body no-padding">
                    <div class="row">
                        <div class="col-xs-12 col-sm-12">
                            <div class="text-center">
                                <h4>
                                    {{ $spell->name }}
                                    @if($canViewSource or $event->data['target']['reveal_ops'])
                                        cast by {{ $event->source->name }}
                                    @endif
                                    on {{ $event->target->name }}
                                </h4>
                            </div>

                            <table class="table">
                                <colgroup>
                                    <col width="50%">
                                    <col width="50%">
                                </colgroup>
                                <tbody>
                                @foreach($event->data['damage'] as $perkKey => $perkDamageData)
                                    <tr>
                                        <td class="text-right">{{ $sorceryHelper->getPerkKeyHeader($perkKey) }}:</td>
                                        <td>
                                              <span class="text-{{ $class }}">
                                                  @if($spell->class == 'active')
                                                      {{ number_format($perkDamageData['damage_dealt']) }}

                                                      @if($perkKey == 'destroy_resource' or $perkKey == 'resource_theft')
                                                          {{ $perkDamageData['resource_name'] }}
                                                      @endif
                                                  @elseif($spell->class == 'passive')
                                                      {{ $perkDamageData  . ' ' . Str::plural('tick', $perkDamageData)}}
                                                  @endif

                                              </span>
                                        </td>
                                    </tr>
                                @endforeach

                                @if($canViewSource)
                                        <td class="text-right">Wizard strength:</td>
                                        <td>{{ number_format($event['data']['caster']['wizard_strength_spent']) }}%</td>
                                    </tr>
                                    <tr>
                                        <td class="text-right">Mana:</td>
                                        <td>{{ number_format($event['data']['caster']['mana_cost']) }}</td>
                                    </tr>
                                @endif

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    <div class="pull-right">
                        <small class="text-muted">
                            Sorcery recorded at
                            {{ $event->created_at }}, tick
                            {{ number_format($event->tick) }}.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
@endsection
