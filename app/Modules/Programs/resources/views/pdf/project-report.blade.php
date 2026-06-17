<!doctype html>
<html lang="{{ $lang ?? 'sk' }}">
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

@php
$l = \Modules\Reporting\Support\CallReportLabels::get($lang ?? 'sk');

$tr = $call->callTranslations->firstWhere('language.name', $lang ?? 'sk')
    ?? $call->callTranslations->first();
$callName        = $tr->name        ?? $call->name        ?? '-';
$callDescription = $tr->description ?? $call->description ?? '-';
@endphp

<h1>{{ $l['title'] }}</h1>
<p class="muted">{{ str_replace(':id', $call->id, $l['generated']) }} {{ now()->format('d.m.Y H:i') }}</p>

<h2>{{ $l['basic_info'] }}</h2>
<table>
    <tr><th>{{ $l['call_name'] }}</th><td>{{ $callName }}</td></tr>
    <tr><th>{{ $l['program'] }}</th><td>{{ $call->program->typeOfProgram->name ?? '-' }}</td></tr>
    <tr><th>{{ $l['partner'] }}</th><td>{{ $call->organization->name ?? '-' }}</td></tr>
    <tr><th>{{ $l['product_owner'] }}</th><td>{{ $call->productOwner ? $call->productOwner->name . ' ' . $call->productOwner->surname . ' (' . $call->productOwner->email . ')' : '-' }}</td></tr>
    <tr><th>{{ $l['deadline'] }}</th><td>{{ $call->application_deadline?->format('d.m.Y') ?? '-' }}</td></tr>
    <tr><th>{{ $l['project_start'] }}</th><td>{{ $call->project_start?->format('d.m.Y') ?? '-' }}</td></tr>
    <tr><th>{{ $l['project_end'] }}</th><td>{{ $call->project_end?->format('d.m.Y') ?? '-' }}</td></tr>
    <tr><th>{{ $l['budget'] }}</th><td>{{ $call->budget ? number_format($call->budget, 2, ',', ' ') . ' €' : '-' }}</td></tr>
    <tr><th>{{ $l['status'] }}</th><td><span class="badge">{{ $call->currentStatusHistory?->status?->name ?? '-' }}</span></td></tr>
</table>

<h2>{{ $l['description'] }}</h2>
<p>{{ $callDescription }}</p>

@php $application = $call->applications->first(); @endphp

@if ($application)

<h2>{{ $l['team'] }}</h2>
@if ($application->team)
<table>
    <tr><th>{{ $l['team_name'] }}</th><td>{{ $application->team->name }}</td></tr>
</table>
<table>
    <tr><th>#</th><th>{{ $l['name'] }}</th><th>{{ $l['email'] }}</th></tr>
    @forelse ($application->team->members as $member)
    <tr>
        <td>{{ $loop->iteration }}</td>
        <td>{{ $member->name }} {{ $member->surname }}</td>
        <td>{{ $member->email }}</td>
    </tr>
    @empty
    <tr><td colspan="3" class="section-empty">{{ $l['no_members'] }}</td></tr>
    @endforelse
</table>
@else
<p class="section-empty">{{ $l['no_team'] }}</p>
@endif

<h2>{{ $l['mentor'] }}</h2>
@forelse ($application->mentorships as $mentorship)
<table>
    <tr><th>{{ $l['name'] }}</th><td>{{ $mentorship->mentor->name }} {{ $mentorship->mentor->surname }}</td></tr>
    <tr><th>{{ $l['email'] }}</th><td>{{ $mentorship->mentor->email }}</td></tr>
</table>
@empty
<p class="section-empty">{{ $l['no_mentor'] }}</p>
@endforelse

@php $commission = $call->commission->first(); @endphp
<h2>{{ $l['commission'] }}</h2>
@if ($commission)
<table>
    <tr><th>{{ $l['commission_name'] }}</th><td>{{ $commission->name }}</td></tr>
</table>
@php $evaluators = $commission->members->whereNull('call_id'); @endphp
@if ($evaluators->isNotEmpty())
<table>
    <tr><th>#</th><th>{{ $l['name'] }}</th><th>{{ $l['email'] }}</th></tr>
    @foreach ($evaluators as $member)
    <tr>
        <td>{{ $loop->iteration }}</td>
        <td>{{ $member->user->name }} {{ $member->user->surname }}</td>
        <td>{{ $member->user->email }}</td>
    </tr>
    @endforeach
</table>
@endif
@php $rep = $call->commissionCompanyRep; @endphp
@if ($rep?->user)
<table>
    <tr><th>{{ $l['company_rep'] }}</th><td>{{ $rep->user->name }} {{ $rep->user->surname }} ({{ $rep->user->email }})</td></tr>
</table>
@endif
@else
<p class="section-empty">{{ $l['no_commission'] }}</p>
@endif

<h2>{{ $l['kpi'] }}</h2>
@if ($application->kpis->isNotEmpty())
<table>
    <tr><th>{{ $l['metric'] }}</th><th>{{ $l['target'] }}</th><th>{{ $l['actual'] }}</th><th>{{ $l['achievement'] }}</th></tr>
    @foreach ($application->kpis as $kpi)
    @php $pct = $kpi->achievement_percentage; @endphp
    <tr>
        <td>{{ $kpi->metric_name }}@if($kpi->unit) ({{ $kpi->unit }})@endif</td>
        <td>{{ $kpi->target_value ?? '-' }}</td>
        <td>{{ $kpi->actual_value ?? '-' }}</td>
        <td class="{{ $kpi->isTargetMet() ? 'kpi-met' : 'kpi-unmet' }}">{{ $pct !== null ? number_format($pct, 1) . ' %' : '-' }}</td>
    </tr>
    @endforeach
</table>
@else
<p class="section-empty">{{ $l['no_kpi'] }}</p>
@endif

<h2>{{ $l['milestones'] }}</h2>
@if ($application->milestones->isNotEmpty())
<table>
    <tr><th>#</th><th>{{ $l['milestone_name'] }}</th><th>{{ $l['milestone_deadline'] }}</th></tr>
    @foreach ($application->milestones->sortBy('deadline') as $milestone)
    <tr>
        <td>{{ $loop->iteration }}</td>
        <td>{{ $milestone->name }}</td>
        <td>{{ $milestone->deadline?->format('d.m.Y') ?? '-' }}</td>
    </tr>
    @endforeach
</table>
@else
<p class="section-empty">{{ $l['no_milestones'] }}</p>
@endif

@else
<p class="section-empty">{{ $l['no_application'] }}</p>
@endif

<h2>{{ $l['criteria'] }}</h2>
@if ($call->callCriteria->isNotEmpty())
<table>
    <tr><th>#</th><th>{{ $l['criterion'] }}</th><th>{{ $l['weight'] }}</th></tr>
    @foreach ($call->callCriteria as $criterion)
    <tr>
        <td>{{ $loop->iteration }}</td>
        <td>{{ $criterion->name }}</td>
        <td>{{ $criterion->pivot->weight ?? '-' }}</td>
    </tr>
    @endforeach
</table>
@else
<p class="section-empty">{{ $l['no_criteria'] }}</p>
@endif

</body>
</html>
