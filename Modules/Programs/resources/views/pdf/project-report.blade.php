<!doctype html>
<html lang="sk">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; margin: 20px; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        h2 { font-size: 13px; margin-top: 20px; margin-bottom: 6px; border-bottom: 1px solid #e5e7eb; padding-bottom: 3px; color: #1e40af; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th, td { border: 1px solid #d1d5db; padding: 5px 8px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; width: 32%; font-weight: bold; }
        .muted { color: #6b7280; font-size: 10px; }
        .badge { display: inline-block; background: #dbeafe; color: #1e40af; padding: 1px 6px; border-radius: 4px; font-size: 10px; }
        .section-empty { color: #9ca3af; font-style: italic; font-size: 10px; }
        .kpi-met { color: #16a34a; }
        .kpi-unmet { color: #dc2626; }
    </style>
</head>
<body>

<h1>Záverečný projektový report</h1>
<p class="muted">Výzva #{{ $call->id }} &nbsp;|&nbsp; Vygenerované: {{ now()->format('d.m.Y H:i') }}</p>

{{-- ZÁKLADNÉ ÚDAJE --}}
<h2>Základné údaje</h2>
<table>
    <tr><th>Názov výzvy</th><td>{{ $call->name ?? '-' }}</td></tr>
    <tr><th>Program</th><td>{{ $call->program->typeOfProgram->name ?? '-' }}</td></tr>
    <tr><th>Partner / Firma</th><td>{{ $call->organization->name ?? '-' }}</td></tr>
    <tr><th>Product Owner</th><td>{{ $call->productOwner ? $call->productOwner->name . ' ' . $call->productOwner->surname . ' (' . $call->productOwner->email . ')' : '-' }}</td></tr>
    <tr><th>Deadline prihlášok</th><td>{{ $call->application_deadline?->format('d.m.Y') ?? '-' }}</td></tr>
    <tr><th>Začiatok projektu</th><td>{{ $call->project_start?->format('d.m.Y') ?? '-' }}</td></tr>
    <tr><th>Koniec projektu</th><td>{{ $call->project_end?->format('d.m.Y') ?? '-' }}</td></tr>
    <tr><th>Rozpočet</th><td>{{ $call->budget ? number_format($call->budget, 2, ',', ' ') . ' €' : '-' }}</td></tr>
    <tr><th>Aktuálny stav</th><td><span class="badge">{{ $call->currentStatusHistory?->status?->name ?? '-' }}</span></td></tr>
</table>

<h2>Popis zadania</h2>
<p>{{ $call->description ?? '-' }}</p>

{{-- TÍM A MENTOR --}}
@php $application = $call->applications->first(); @endphp

@if ($application)

<h2>Realizujúci tím</h2>
@if ($application->team)
<table>
    <tr><th>Názov tímu</th><td>{{ $application->team->name }}</td></tr>
</table>
<table>
    <tr>
        <th>#</th>
        <th>Meno</th>
        <th>Email</th>
    </tr>
    @forelse ($application->team->members as $member)
    <tr>
        <td>{{ $loop->iteration }}</td>
        <td>{{ $member->name }} {{ $member->surname }}</td>
        <td>{{ $member->email }}</td>
    </tr>
    @empty
    <tr><td colspan="3" class="section-empty">Žiadni členovia</td></tr>
    @endforelse
</table>
@else
<p class="section-empty">Tím nebol pridelený.</p>
@endif

<h2>Mentor</h2>
@forelse ($application->mentorships as $mentorship)
<table>
    <tr><th>Meno</th><td>{{ $mentorship->mentor->name }} {{ $mentorship->mentor->surname }}</td></tr>
    <tr><th>Email</th><td>{{ $mentorship->mentor->email }}</td></tr>
</table>
@empty
<p class="section-empty">Mentor nebol pridelený.</p>
@endforelse

{{-- KPI --}}
<h2>KPI metriky</h2>
@if ($application->kpis->isNotEmpty())
<table>
    <tr>
        <th>Metrika</th>
        <th>Cieľ</th>
        <th>Skutočnosť</th>
        <th>Plnenie</th>
    </tr>
    @foreach ($application->kpis as $kpi)
    @php $pct = $kpi->achievement_percentage; @endphp
    <tr>
        <td>{{ $kpi->metric_name }}@if($kpi->unit) ({{ $kpi->unit }})@endif</td>
        <td>{{ $kpi->target_value ?? '-' }}</td>
        <td>{{ $kpi->actual_value ?? '-' }}</td>
        <td class="{{ $kpi->isTargetMet() ? 'kpi-met' : 'kpi-unmet' }}">
            {{ $pct !== null ? number_format($pct, 1) . ' %' : '-' }}
        </td>
    </tr>
    @endforeach
</table>
@else
<p class="section-empty">Žiadne KPI neboli zadané.</p>
@endif

{{-- VÝSTUPY --}}
<h2>Projektové výstupy</h2>
@if ($application->outputs->isNotEmpty())
<table>
    <tr>
        <th>Výstup</th>
        <th>Typ</th>
        <th>Plánované odovzdanie</th>
        <th>Skutočné odovzdanie</th>
        <th>Stav</th>
    </tr>
    @foreach ($application->outputs as $output)
    <tr>
        <td>{{ $output->output_name }}</td>
        <td>{{ $output->output_type ?? '-' }}</td>
        <td>{{ $output->planned_delivery?->format('d.m.Y') ?? '-' }}</td>
        <td>{{ $output->actual_delivery?->format('d.m.Y') ?? '-' }}</td>
        <td>{{ $output->getDeliveryStatusLabel() }}</td>
    </tr>
    @endforeach
</table>
@else
<p class="section-empty">Žiadne výstupy neboli zadané.</p>
@endif

{{-- MÍĽNIKY --}}
<h2>Projektové míľniky</h2>
@if ($application->milestones->isNotEmpty())
<table>
    <tr>
        <th>#</th>
        <th>Názov</th>
        <th>Deadline</th>
        <th>Stav</th>
        <th>Komentár</th>
    </tr>
    @foreach ($application->milestones->sortBy('deadline') as $milestone)
    <tr>
        <td>{{ $loop->iteration }}</td>
        <td>{{ $milestone->name }}</td>
        <td>{{ $milestone->deadline?->format('d.m.Y') ?? '-' }}</td>
        <td>{{ $milestone->status ?? '-' }}</td>
        <td>{{ $milestone->comments ?? '-' }}</td>
    </tr>
    @endforeach
</table>
@else
<p class="section-empty">Žiadne míľniky neboli zadané.</p>
@endif

@else
<p class="section-empty">K tejto výzve neexistuje schválená prihláška.</p>
@endif

{{-- KRITÉRIÁ --}}
<h2>Hodnotiace kritériá</h2>
@if ($call->callCriteria->isNotEmpty())
<table>
    <tr>
        <th>#</th>
        <th>Kritérium</th>
        <th>Váha</th>
    </tr>
    @foreach ($call->callCriteria as $criterion)
    <tr>
        <td>{{ $loop->iteration }}</td>
        <td>{{ $criterion->name }}</td>
        <td>{{ $criterion->pivot->weight ?? '-' }}</td>
    </tr>
    @endforeach
</table>
@else
<p class="section-empty">Žiadne kritériá.</p>
@endif

</body>
</html>
