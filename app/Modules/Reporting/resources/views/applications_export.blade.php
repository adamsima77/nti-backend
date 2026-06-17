<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Export prihlášok</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DejaVu Sans', 'Arial', sans-serif;
            font-size: 10px;
            color: #1a1a2e;
            background: #ffffff;
            padding: 24px 28px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 22px;
            padding-bottom: 16px;
            border-bottom: 3px solid #1e3a5f;
        }

        .brand-name {
            font-size: 18px;
            font-weight: 700;
            color: #1e3a5f;
            letter-spacing: -0.3px;
        }

        .brand-sub {
            font-size: 11px;
            color: #64748b;
            font-weight: 500;
            margin-top: 2px;
        }

        .meta {
            text-align: right;
            color: #64748b;
            line-height: 1.7;
        }

        .meta strong { color: #1e3a5f; }

        .filters-row {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 16px;
        }

        .filter-pill {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 99px;
            padding: 2px 10px;
            font-size: 9px;
            font-weight: 600;
            color: #1d4ed8;
        }

        .filter-pill span { font-weight: 400; color: #3b82f6; }

        .stats-row {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
        }

        .stat-box {
            flex: 1;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 14px;
        }

        .stat-label {
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #94a3b8;
            margin-bottom: 4px;
        }

        .stat-value {
            font-size: 20px;
            font-weight: 800;
            color: #1e3a5f;
            line-height: 1;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5px;
        }

        thead tr { background: #1e3a5f; color: #ffffff; }

        thead th {
            padding: 9px 10px;
            text-align: left;
            font-weight: 700;
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        thead th:first-child { border-radius: 6px 0 0 0; }
        thead th:last-child  { border-radius: 0 6px 0 0; }

        tbody tr { border-bottom: 1px solid #e2e8f0; }
        tbody tr:nth-child(even) { background: #f0f4fa; }
        tbody tr:last-child { border-bottom: none; }

        tbody td {
            padding: 7px 10px;
            vertical-align: top;
            color: #334155;
            line-height: 1.45;
        }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 99px;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            white-space: nowrap;
        }

        .badge-draft       { background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1; }
        .badge-submitted   { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .badge-evaluating  { background: #fefce8; color: #a16207; border: 1px solid #fde68a; }
        .badge-pending     { background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; }
        .badge-approved    { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
        .badge-rejected    { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .badge-paused      { background: #faf5ff; color: #7e22ce; border: 1px solid #e9d5ff; }
        .badge-onboarding  { background: #f0f9ff; color: #0369a1; border: 1px solid #bae6fd; }
        .badge-active      { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
        .badge-completed   { background: #f8fafc; color: #334155; border: 1px solid #94a3b8; }

        .mono {
            font-family: 'Courier New', monospace;
            font-size: 9px;
            color: #475569;
        }

        .empty {
            text-align: center;
            padding: 40px;
            color: #94a3b8;
            font-style: italic;
        }

        .page-footer {
            margin-top: 20px;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            color: #94a3b8;
            font-size: 8px;
        }

        @media print {
            thead { display: table-header-group; }
            tr    { page-break-inside: avoid; }
        }
    </style>
</head>
<body>

{{--
  $applications is an array of stdClass objects produced by:
      json_decode(json_encode($collection->map(fn($a) => $a->toArray())->all()))

  This means:
    - Use count($applications), not $applications->count()
    - Use $app->team->name (stdClass, deep), not $app->team?->name (PHP 8 nullsafe on array)
    - $app->mentorships is an array of stdClass — use foreach, not Collection methods
    - Date fields (submitted_at, created_at) are already strings from Carbon::toArray()
--}}

@php
    // $filters arrives as a plain PHP array in the sync path, but as a stdClass
    // in the async path because the queued job stores view-data as JSON and
    // decodes it with json_decode() (no associative flag).
    // Cast once to array here so the rest of the blade is always consistent.
    $f = is_array($filters) ? $filters : (array) $filters;

    $total       = count($applications);
    $submitted   = 0;
    $withMentor  = 0;
    $statusNames = [];

    foreach ($applications as $app) {
        if (! empty($app->submitted_at)) {
            $submitted++;
        }
        if (! empty($app->mentorships)) {
            $withMentor++;
        }
        $sName = $app->status->name ?? 'Neznámy';
        $statusNames[$sName] = true;
    }

    $distinctStatuses = count($statusNames);

    $statusBadgeMap = [
      'Draft'               => 'draft',
      'Podané'              => 'submitted',
      'V hodnotení'         => 'evaluating',
      'Vyžiadané doplnenie' => 'pending',
      'Schválené'           => 'approved',
      'Zamietnuté'          => 'rejected',
      'Pozastavené'         => 'paused',
      'Onboarding'          => 'onboarding',
      'Aktívny projekt'     => 'active',
      'Ukončené'            => 'completed',
    ];

    $activeFilters = array_filter([
      'Výzva ID'   => $f['call_id']        ?? null,
      'Stav ID'    => $f['status_id']       ?? null,
      'Podané od'  => $f['submitted_from']  ?? null,
      'Podané do'  => $f['submitted_to']    ?? null,
    ]);
@endphp

    <!-- ── Header ─────────────────────────────────────────────────────────── -->
<div class="page-header">
    <div>
        <div class="brand-name">NTI Portál</div>
        <div class="brand-sub">Export prihlášok</div>
    </div>
    <div class="meta">
        <div>Vygenerované: <strong>{{ $generatedAt }}</strong></div>
        <div>Počet záznamov: <strong>{{ $total }}</strong></div>
    </div>
</div>

<!-- ── Active filters ─────────────────────────────────────────────────── -->
@if(count($activeFilters))
    <div class="filters-row">
        @foreach($activeFilters as $label => $val)
            <div class="filter-pill">{{ $label }}: <span>{{ $val }}</span></div>
        @endforeach
    </div>
@endif

<!-- ── Summary stats ──────────────────────────────────────────────────── -->
<div class="stats-row">
    <div class="stat-box">
        <div class="stat-label">Celkom prihlášok</div>
        <div class="stat-value">{{ $total }}</div>
    </div>
    <div class="stat-box">
        <div class="stat-label">Podaných</div>
        <div class="stat-value">{{ $submitted }}</div>
    </div>
    <div class="stat-box">
        <div class="stat-label">S mentorom</div>
        <div class="stat-value">{{ $withMentor }}</div>
    </div>
    <div class="stat-box">
        <div class="stat-label">Počet stavov</div>
        <div class="stat-value">{{ $distinctStatuses }}</div>
    </div>
</div>

<!-- ── Table ──────────────────────────────────────────────────────────── -->
<table>
    <thead>
    <tr>
        <th>ID</th>
        <th>Referencia</th>
        <th>Tím</th>
        <th>Výzva</th>
        <th>Stav</th>
        <th>Mentor(i)</th>
        <th>Vytvoril</th>
        <th>Dátum podania</th>
    </tr>
    </thead>
    <tbody>
    @forelse($applications as $app)
        @php
            $statusName = $app->status->name ?? '—';
            $badgeClass = 'badge-' . ($statusBadgeMap[$statusName] ?? 'draft');

            // Build mentor string from stdClass mentorships array
            $mentorParts = [];
            foreach ($app->mentorships ?? [] as $ms) {
                if (! empty($ms->mentor)) {
                    $mentorParts[] = trim(($ms->mentor->name ?? '') . ' ' . ($ms->mentor->surname ?? ''));
                }
            }
            $mentorStr = implode(', ', array_filter($mentorParts)) ?: '—';

            // submitted_at is already a formatted string after json_encode/decode of toArray()
            // It may be null or a full datetime string — display only the date+time part
            $submittedAt = $app->submitted_at ?? null;
            if ($submittedAt) {
                try {
                    $submittedAt = \Carbon\Carbon::parse($submittedAt)->format('d.m.Y H:i');
                } catch (\Throwable) {
                    // keep raw value
                }
            }
        @endphp
        <tr>
            <td class="mono">{{ $app->id }}</td>
            <td class="mono">{{ $app->reference ?? '—' }}</td>
            <td><strong>{{ $app->team->name ?? '—' }}</strong></td>
            <td>{{ $app->call->name ?? '—' }}</td>
            <td><span class="badge {{ $badgeClass }}">{{ $statusName }}</span></td>
            <td>{{ $mentorStr }}</td>
            <td>{{ $app->creator->email ?? '—' }}</td>
            <td class="mono">{{ $submittedAt ?? '—' }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="8" class="empty">Žiadne prihlášky nevyhovujú zvoleným filtrom.</td>
        </tr>
    @endforelse
    </tbody>
</table>

<!-- ── Footer ─────────────────────────────────────────────────────────── -->
<div class="page-footer">
    <span>NTI Portál — Dôverný dokument</span>
    <span>Generované automaticky systémom {{ $generatedAt }}</span>
</div>

</body>
</html>
