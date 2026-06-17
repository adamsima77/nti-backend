<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>GDPR Personal Data Report</title>
    <style>
        /*
         * DomPDF supports a subset of CSS2.1.
         * - No flexbox / grid
         * - Use DejaVu Sans for proper UTF-8 (e.g. IČO with diacritics)
         * - Percentage widths work; avoid calc()
         */

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #2d2d2d;
            background: #ffffff;
        }

        /* ── Header ──────────────────────────────────────────── */
        .report-header {
            background-color: #1a1a2e;
            color: #ffffff;
            padding: 22px 30px 18px;
        }
        .report-header h1 {
            font-size: 18px;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .report-header .meta {
            font-size: 9px;
            opacity: 0.75;
        }

        /* ── Body ────────────────────────────────────────────── */
        .body {
            padding: 24px 30px 10px;
        }

        /* ── Section ─────────────────────────────────────────── */
        .section {
            margin-bottom: 22px;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #1a1a2e;
            border-bottom: 2px solid #1a1a2e;
            padding-bottom: 5px;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        /* ── Tables ──────────────────────────────────────────── */
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 6px 9px;
            border: 1px solid #dde1e7;
            font-size: 10px;
            vertical-align: top;
        }
        th {
            background-color: #f0f2f7;
            color: #555;
            font-weight: bold;
            width: 36%;
            white-space: nowrap;
        }
        td {
            color: #333;
            word-break: break-word;
        }
        tr:nth-child(even) td {
            background-color: #f8f9fc;
        }

        /* consent table — no fixed th width */
        .table-full th {
            width: auto;
        }

        /* ── Empty state ─────────────────────────────────────── */
        .empty {
            font-style: italic;
            color: #999;
            font-size: 10px;
        }

        /* ── Footer ──────────────────────────────────────────── */
        .report-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 10px 30px;
            border-top: 1px solid #dde1e7;
            font-size: 8px;
            color: #aaa;
            text-align: center;
        }

        /* DomPDF page counter */
        .page-number:before {
            content: counter(page);
        }
        .page-total:before {
            content: counter(pages);
        }
    </style>
</head>
<body>

{{-- ════════════ HEADER ════════════ --}}
<div class="report-header">
    <h1>GDPR Personal Data Report</h1>
    <div class="meta">
        Generated: {{ now()->format('d.m.Y H:i:s') }}
        &nbsp;|&nbsp;
        Subject User ID: {{ $user->id }}
        &nbsp;|&nbsp;
        Role(s): {{ $user->roles->pluck('name')->implode(', ') }}
    </div>
</div>

{{-- ════════════ BODY ════════════ --}}
<div class="body">

    {{-- ── 1. Personal Information ──────────────────────── --}}
    <div class="section">
        <div class="section-title">Personal Information</div>
        <table>
            <tr><th>User ID</th><td>{{ $user->id }}</td></tr>
            <tr><th>First Name</th><td>{{ $user->name }}</td></tr>
            <tr><th>Last Name</th><td>{{ $user->surname }}</td></tr>
            <tr><th>Email Address</th><td>{{ $user->email }}</td></tr>
            <tr><th>Role(s)</th><td>{{ $user->roles->pluck('name')->implode(', ') }}</td></tr>
            <tr><th>Account Created</th><td>{{ $user->created_at->format('d.m.Y H:i:s') }}</td></tr>
        </table>
    </div>

    {{-- ── 2. Consent Records ────────────────────────────── --}}
    <div class="section">
        <div class="section-title">Consent Records</div>

        @if($user->userConsents->isEmpty())
            <p class="empty">No consent records found for this user.</p>
        @else
            <table class="table-full">
                <tr>
                    <th>Consent Name</th>
                    <th>Granted At</th>
                </tr>
                @foreach($user->userConsents as $userConsent)
                    <tr>
                        <td>{{ $userConsent->consent?->name ?? 'Unknown' }}</td>
                        <td>{{ $userConsent->created_at?->format('d.m.Y H:i:s') ?? 'N/A' }}</td>
                    </tr>
                @endforeach
            </table>
        @endif
    </div>

    {{-- ── 3. Partner: Organization Data ───────────────── --}}
    @if($user->roles->contains('name', 'partner'))
        @php
            // Pull the single organization model instance out of the collection safely
            $org     = $user->organizations->first();
            $address = $org?->address;
            $langId  = \Modules\Content\Enums\LanguageType::ENGLISH->value;
        @endphp

        <div class="section">
            <div class="section-title">Organization Information</div>

            <table>
                <tr><th>Organization Name</th><td>{{ $org?->name            ?? 'N/A' }}</td></tr>
                <tr><th>Phone</th>            <td>{{ $org?->phone           ?? 'N/A' }}</td></tr>
                <tr><th>IČO</th>              <td>{{ $org?->ico             ?? 'N/A' }}</td></tr>
                <tr><th>Website</th>          <td>{{ $org?->web_url         ?? 'N/A' }}</td></tr>
                <tr><th>Description</th>      <td>{{ $org?->description     ?? 'N/A' }}</td></tr>
                <tr>
                    <th>Sectors</th>
                    <td>
                        @if($org && $org->sectors && $org->sectors->isNotEmpty())
                            {{
                                $org->sectors->map(function($sector) use ($langId) {
                                    // 1. Look for explicit sectorTranslations collection
                                    if (isset($sector->sectorTranslations)) {
                                        return $sector->sectorTranslations->firstWhere('language_id', $langId)?->name
                                            ?? $sector->sectorTranslations->first()?->name;
                                    }
                                    // 2. Generic fallback relationship name
                                    if (isset($sector->translations)) {
                                        return $sector->translations->firstWhere('language_id', $langId)?->name
                                            ?? $sector->translations->first()?->name;
                                    }
                                    // 3. Fallback to direct raw table column name
                                    return $sector->name;
                                })
                                ->filter(fn($val) => !empty(trim($val)))
                                ->implode(', ') ?: 'N/A'
                            }}
                        @else
                            N/A
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        @if($address)
            <div class="section">
                <div class="section-title">Organization Address</div>
                <table>
                    <tr><th>Country</th>     <td>{{ $address->country     ?? 'N/A' }}</td></tr>
                    <tr><th>City</th>        <td>{{ $address->city        ?? 'N/A' }}</td></tr>
                    <tr><th>Street</th>      <td>{{ $address->street      ?? 'N/A' }}</td></tr>
                    <tr><th>Postal Code</th> <td>{{ $address->postal_code ?? 'N/A' }}</td></tr>
                </table>
            </div>
        @endif
    @endif

    {{-- ── 4. Student: Academic Data ────────────────────── --}}
    @if($user->roles->contains('name', 'student'))
        @php
            $student  = $user->student;
            $langId   = \Modules\Content\Enums\LanguageType::ENGLISH->value;

            $studyYear = $student?->studyYear?->studyYearTranslations
                ->firstWhere('language_id', $langId)?->name
                ?? $student?->studyYear?->studyYearTranslations->first()?->name
                ?? 'N/A';

            $studyProgram = $student?->studyProgram?->studyProgramTranslations
                ->firstWhere('language_id', $langId)?->name
                ?? $student?->studyProgram?->studyProgramTranslations->first()?->name
                ?? 'N/A';

            $studyField = $student?->studyField?->studyFieldTranslations
                ->firstWhere('language_id', $langId)?->name
                ?? $student?->studyField?->studyFieldTranslations->first()?->name
                ?? 'N/A';
        @endphp

        <div class="section">
            <div class="section-title">Academic Information</div>
            <table>
                <tr><th>Study Year</th>    <td>{{ $studyYear }}</td></tr>
                <tr><th>Study Program</th> <td>{{ $studyProgram }}</td></tr>
                <tr><th>University</th>    <td>{{ $student?->university?->name ?? 'N/A' }}</td></tr>
                <tr><th>Study Field</th>   <td>{{ $studyField }}</td></tr>
            </table>
        </div>
    @endif

</div>{{-- /.body --}}

{{-- ════════════ FOOTER ════════════ --}}
<div class="report-footer">
    Generated in compliance with the General Data Protection Regulation (GDPR) — Article 15 (Right of Access by the Data Subject).
    &nbsp;|&nbsp;
    Page <span class="page-number"></span> of <span class="page-total"></span>
</div>

</body>
</html>
