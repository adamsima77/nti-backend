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
<h2>{{ __('Export Výziev') }}</h2>
<p class="text-muted">Dátum generovania: {{ now()->format('d.m.Y H:i') }}</p>

<table>
    <thead>
    <tr>
        <th>ID</th>
        <th>Názov</th>
        <th>Popis</th>
        <th>Deadline prihlášok</th>
        <th>Začiatok projektu</th>
        <th>Koniec projektu</th>
    </tr>
    </thead>
    <tbody>
    @foreach($calls as $call)
        <tr>
            <td>{{ data_get($call, 'id') }}</td>
            <td><strong>{{ data_get($call, 'name') }}</strong></td>
            <td>{{ strip_tags(substr(data_get($call, 'description', ''), 0, 150)) }}...</td>
            <td>{{ data_get($call, 'application_deadline') ? \Carbon\Carbon::parse(data_get($call, 'application_deadline'))->format('d.m.Y H:i') : '—' }}</td>
            <td>{{ data_get($call, 'project_start') ? \Carbon\Carbon::parse(data_get($call, 'project_start'))->format('d.m.Y') : '—' }}</td>
            <td>{{ data_get($call, 'project_end') ? \Carbon\Carbon::parse(data_get($call, 'project_end'))->format('d.m.Y') : '—' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
</body>
</html>
