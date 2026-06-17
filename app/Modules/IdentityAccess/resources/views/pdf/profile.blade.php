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
    <h1>Profil používateľa #{{ $user->id }}</h1>
    <p class="muted">Vygenerované: {{ now()->format('d.m.Y H:i') }}</p>

    <h2>Základné údaje</h2>
    <table>
        <tr><th>Meno</th><td>{{ $user->name }}</td></tr>
        <tr><th>Priezvisko</th><td>{{ $user->surname }}</td></tr>
        <tr><th>Email</th><td>{{ $user->email }}</td></tr>
        <tr><th>Status</th><td>{{ $user->status->name ?? '-' }}</td></tr>
        <tr><th>Overený email</th><td>{{ $user->email_verified_at ? $user->email_verified_at->format('d.m.Y H:i') : '-' }}</td></tr>
    </table>

    <h2>Roly</h2>
    <table>
        <tr>
            <th>#</th>
            <th>Rola</th>
        </tr>
        @forelse ($user->roles as $role)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $role->name }}</td>
            </tr>
        @empty
            <tr><td colspan="2">Bez rolí</td></tr>
        @endforelse
    </table>

    <h2>Tímy</h2>
    <table>
        <tr>
            <th>#</th>
            <th>Tím</th>
        </tr>
        @forelse ($user->teams as $team)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $team->name }}</td>
            </tr>
        @empty
            <tr><td colspan="2">Bez tímov</td></tr>
        @endforelse
    </table>
</body>
</html>
