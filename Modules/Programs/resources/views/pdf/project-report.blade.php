<!doctype html>
<html lang="sk">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        h1 { font-size: 20px; margin-bottom: 8px; }
        h2 { font-size: 14px; margin-top: 18px; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 6px 8px; text-align: left; }
        th { background: #f3f4f6; width: 32%; }
        .muted { color: #6b7280; }
    </style>
</head>
<body>
    <h1>Projektový report výzvy #{{ $call->id }}</h1>
    <p class="muted">Vygenerované: {{ now()->format('d.m.Y H:i') }}</p>

    <h2>Základné údaje</h2>
    <table>
        <tr><th>ID</th><td>{{ $call->id }}</td></tr>
        <tr><th>Názov</th><td>{{ $call->name }}</td></tr>
        <tr><th>Program</th><td>{{ $call->program->name ?? '-' }}</td></tr>
        <tr><th>Partner</th><td>{{ $call->organization->name ?? '-' }}</td></tr>
        <tr><th>Deadline prihlášok</th><td>{{ optional($call->application_deadline)->format('d.m.Y H:i') }}</td></tr>
        <tr><th>Začiatok projektu</th><td>{{ optional($call->project_start)->format('d.m.Y H:i') }}</td></tr>
        <tr><th>Koniec projektu</th><td>{{ optional($call->project_end)->format('d.m.Y H:i') }}</td></tr>
        <tr><th>Aktuálny stav</th><td>{{ $call->currentStatusHistory->status->name ?? '-' }}</td></tr>
    </table>

    <h2>Popis</h2>
    <p>{{ $call->description }}</p>

    <h2>Kritériá</h2>
    <table>
        <tr>
            <th>#</th>
            <th>Kritérium</th>
        </tr>
        @forelse ($call->callCriteria as $criterion)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $criterion->name }}</td>
            </tr>
        @empty
            <tr><td colspan="2">Bez kritérií</td></tr>
        @endforelse
    </table>
</body>
</html>
