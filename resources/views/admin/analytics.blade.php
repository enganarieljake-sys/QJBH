@extends('layouts.app')

@section('title', 'Analytics')
@section('page-title', 'Analytics')
@section('page-subtitle', 'A clear view of income, rooms, bills, and repairs.')
@section('page-actions')
    <a href="{{ route('admin.dashboard') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-white">Back to dashboard</a>
@endsection

@php
    $summary = $data['summary'];
    $now = $summary['this_month'];
    $before = $summary['last_month'];
    $change = $summary['change'];
    $peso = fn ($amount) => '₱'.number_format($amount, 2);
    // Doughnut cards: title, data key, colors in label order, and a one-line hint.
    $doughnuts = [
        ['Rooms', 'rooms', ['#0879ed', '#16a34a', '#f59e0b'], 'How many are full, have space, or are being repaired.'],
        ['Repairs', 'repairs', ['#e11d48', '#f59e0b', '#0879ed', '#16a34a'], 'Where every repair stands right now.'],
        ['Bills for '.$now['label'], 'bills', ['#16a34a', '#0879ed', '#e11d48'], 'How many are paid and how many are not.'],
        ['Payment methods ('.$now['label'].')', 'methods', ['#0879ed', '#16a34a', '#f59e0b', '#8b5cf6'], 'Cash, GCash, or bank.'],
    ];
@endphp

@section('content')
    <div class="analytics" data-analytics>
        <script type="application/json" data-analytics-data>@json($data)</script>

        <section class="analytics-hero">
            <div>
                <p class="analytics-hero__label">Income in {{ $now['label'] }}</p>
                <p class="analytics-hero__amount">{{ $peso($now['income']) }}</p>
                @if ($change !== null)
                    <p class="analytics-hero__change {{ $change >= 0 ? 'is-up' : 'is-down' }}">
                        {{ $change >= 0 ? '▲' : '▼' }} {{ abs($change) }}% vs {{ $before['label'] }}
                    </p>
                @else
                    <p class="analytics-hero__change">No income in {{ $before['label'] }} to compare.</p>
                @endif
            </div>
            <dl class="analytics-hero__stats">
                <div><dt>Income in {{ $before['label'] }}</dt><dd>{{ $peso($before['income']) }}</dd></div>
                <div><dt>Expenses in {{ $now['label'] }}</dt><dd>{{ $peso($now['expenses']) }}</dd></div>
                <div><dt>Net in {{ $now['label'] }}</dt><dd>{{ $peso($now['income'] - $now['expenses']) }}</dd></div>
            </dl>
        </section>

        <section class="analytics-card analytics-card--wide">
            <h2>Income and expenses (6 months)</h2>
            <p>Blue is income, red is expenses. The taller the blue, the better.</p>
            <div class="analytics-card__chart analytics-card__chart--bar"><canvas data-chart="trend" aria-label="Bar graph of income and expenses for the last six months" role="img"></canvas></div>
        </section>

        <div class="analytics-grid">
            @foreach ($doughnuts as [$title, $key, $colors, $hint])
                @php $values = $data[$key]; $total = array_sum($values); @endphp
                <section class="analytics-card">
                    <h2>{{ $title }}</h2>
                    <p>{{ $hint }}</p>
                    @if ($total > 0)
                        <div class="analytics-card__chart"><canvas data-chart="{{ $key }}" data-colors="{{ implode(',', $colors) }}" aria-label="{{ $title }} chart" role="img"></canvas></div>
                        <ul class="analytics-legend">
                            @foreach ($values as $label => $value)
                                <li><span style="background: {{ $colors[$loop->index] ?? '#94a3b8' }}"></span>{{ $label }} <b>{{ $key === 'methods' ? $peso($value) : $value }}</b></li>
                            @endforeach
                        </ul>
                    @else
                        <p class="analytics-card__empty">No data yet.</p>
                    @endif
                </section>
            @endforeach
        </div>
    </div>
@endsection
