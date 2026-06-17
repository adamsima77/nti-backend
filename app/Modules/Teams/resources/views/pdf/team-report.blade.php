<!doctype html>
<html lang="sk">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        h1 { font-size: 20px; margin-bottom: 8px; }
        h2 { font-size: 14px; margin-top: 18px; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; width: 32%; }
        .muted { color: #6b7280; }
    </style>
</head>
<body>
    <h1>Report tímu #{{ $team->id }}</h1>
    <p class="muted">Vygenerované: {{ now()->format('d.m.Y H:i') }}</p>

    <h2>Základné údaje</h2>
    <table>
        <tr><th>Názov tímu</th><td>{{ $team->name }}</td></tr>
        <tr><th>Počet členov</th><td>{{ $team->members->count() }}</td></tr>
    </table>

    <h2>Členovia</h2>
    <table>
        <tr>
            <th>#</th>
            <th>Meno</th>
            <th>Email</th>
        </tr>
        @forelse ($team->members as $member)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $member->name }} {{ $member->surname }}</td>
                <td>{{ $member->email }}</td>
            </tr>
        @empty
            <tr><td colspan="3">Bez členov</td></tr>
        @endforelse
    </table>
</body>
</html>
