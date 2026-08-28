<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $settings['title'] }}</title>
    <style>
        @page {
            margin: 36pt 40pt;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #111827;
            font-size: 11pt;
            line-height: 1.4;
        }

        h1 {
            font-size: 18pt;
            margin: 0 0 6pt;
            letter-spacing: 0.04em;
        }

        .meta {
            margin-bottom: 18pt;
            font-size: 10pt;
            color: #374151;
        }

        .meta p {
            margin: 0 0 3pt;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 7pt 8pt;
            text-align: left;
            vertical-align: top;
        }

        th {
            font-size: 9pt;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #374151;
            border-bottom: 1pt solid #9ca3af;
        }

        td {
            border-bottom: 0.5pt solid #e5e7eb;
        }

        .date {
            white-space: nowrap;
            font-weight: 600;
        }

        .unassigned {
            color: #b91c1c;
            font-style: italic;
        }
    </style>
</head>
<body>
    <h1>{{ $settings['title'] }}</h1>

    <div class="meta">
        @if ($settings['congregation'] !== '')
            <p>{{ $settings['congregation'] }}</p>
        @endif
        @if ($settings['address'] !== '')
            <p>{{ $settings['address'] }}</p>
        @endif
        <p>{{ $periodLabel }}</p>
    </div>

    @if ($rows === [])
        <p>No schedule entries for this period.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Audio</th>
                    <th>Video</th>
                    <th>Mics</th>
                    <th>Stage</th>
                    @if ($settings['include_preparation'])
                        <th>Preparation</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td class="date">{{ $row['date_label'] }}</td>
                        <td @class(['unassigned' => $row['audio'] === 'Unassigned'])>{{ $row['audio'] }}</td>
                        <td @class(['unassigned' => $row['video'] === 'Unassigned'])>{{ $row['video'] }}</td>
                        <td @class(['unassigned' => $row['mics'] === 'Unassigned'])>{{ $row['mics'] }}</td>
                        <td @class(['unassigned' => $row['stage'] === 'Unassigned'])>{{ $row['stage'] }}</td>
                        @if ($settings['include_preparation'])
                            <td @class(['unassigned' => $row['preparation'] === 'Unassigned'])>{{ $row['preparation'] }}</td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
