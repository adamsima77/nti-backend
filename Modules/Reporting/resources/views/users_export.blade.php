<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #1e293b; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #f1f5f9; font-weight: bold; text-align: left; border: 1px solid #cbd5e1; padding: 8px; }
        td { border: 1px solid #cbd5e1; padding: 8px; vertical-align: top; }
        .text-muted { color: #94a3b8; }
    </style>
</head>
<body>
<h2>{{ __('Export používateľov') }}</h2>
<p class="text-muted">Dátum generovania: {{ now()->format('d.m.Y H:i') }}</p>

<table>
    <thead>
    <tr>
        <th>ID</th>
        <th>Meno</th>
        <th>Priezvisko</th>
        <th>Email</th>
        <th>Status</th>
        <th>Vytvorené</th>
    </tr>
    </thead>
    <tbody>
    @foreach($users as $user)
        <tr>
            <td>{{ data_get($user, 'id') }}</td>
            <td>{{ data_get($user, 'name') }}</td>
            <td>{{ data_get($user, 'surname') }}</td>
            <td>{{ data_get($user, 'email') }}</td>
            <td>{{ data_get($user, 'status.name') ?? '—' }}</td>
            <td>{{ data_get($user, 'created_at') ? \Carbon\Carbon::parse(data_get($user, 'created_at'))->format('d.m.Y H:i') : '—' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
</body>
</html>
