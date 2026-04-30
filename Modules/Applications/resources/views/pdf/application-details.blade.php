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
    <h1>Detail prihlášky #{{ $application->id }}</h1>
    <p class="muted">Vygenerované: {{ now()->format('d.m.Y H:i') }}</p>

    <h2>Základné údaje</h2>
    <table>
        <tr><th>ID</th><td>{{ $application->id }}</td></tr>
        <tr><th>Výzva</th><td>{{ $application->call->name ?? '-' }}</td></tr>
        <tr><th>Tím</th><td>{{ $application->team_id }}</td></tr>
        <tr><th>Stav</th><td>{{ $application->status->name ?? '-' }}</td></tr>
        <tr><th>Odoslané</th><td>{{ optional($application->submitted_at)->format('d.m.Y H:i') }}</td></tr>
        <tr><th>Posledná zmena</th><td>{{ optional($application->last_update)->format('d.m.Y H:i') }}</td></tr>
    </table>

    <h2>Prílohy</h2>
    <table>
        <tr>
            <th>#</th>
            <th>Dokument</th>
        </tr>
        @forelse ($application->documents as $document)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $document->id }}</td>
            </tr>
        @empty
            <tr><td colspan="2">Bez príloh</td></tr>
        @endforelse
    </table>

    <h2>História stavu</h2>
    <table>
        <tr>
            <th>Dátum</th>
            <th>Stav</th>
            <th>Poznámka</th>
        </tr>
        @forelse ($application->statusHistory as $item)
            <tr>
                <td>{{ optional($item->created_at)->format('d.m.Y H:i') }}</td>
                <td>{{ $item->status->name ?? '-' }}</td>
                <td>{{ $item->note ?? '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="3">Bez histórie</td></tr>
        @endforelse
    </table>
</body>
</html>
