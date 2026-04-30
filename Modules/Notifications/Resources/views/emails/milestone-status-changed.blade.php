<!doctype html>
<html lang="sk">
<head>
    <meta charset="utf-8">
</head>
<body>
    <p>Ahoj {{ $user->name }} {{ $user->surname }},</p>
    <p>míľnik <strong>{{ $milestone->name }}</strong> prešiel z stavu <strong>{{ $oldStatus ?? '-' }}</strong> na <strong>{{ $newStatus ?? '-' }}</strong>.</p>
    <p>Deadline: {{ optional($milestone->deadline)->format('d.m.Y') }}</p>
    <p>Akciu vykonal: {{ $user->name }} {{ $user->surname }}</p>
</body>
</html>
