<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }} report</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10pt; margin: 15mm; }
        h1 { font-size: 16pt; margin: 0; } .meta { color: #555; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; } th, td { border: 1px solid #bbb; padding: 4px 6px; text-align: left; }
        th { background: #0b3d91; color: #fff; } tr:nth-child(even) td { background: #f3f5fa; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
<p class="no-print"><button onclick="window.print()">Print / Save as PDF</button></p>
<h1>{{ \App\Support\LibrarySettings::get('library_name') }} — {{ $title }}</h1>
<div class="meta">Period {{ $from->format('d M Y') }} – {{ $to->format('d M Y') }} · Generated {{ now()->format('d M Y H:i') }} by {{ auth()->user()->name }} · {{ $rows->count() }} rows</div>
@if($rows->isEmpty())
    <p>No records for this period.</p>
@else
    <table>
        <thead><tr>@foreach(array_keys($rows->first()) as $h)<th>{{ $h }}</th>@endforeach</tr></thead>
        <tbody>@foreach($rows as $row)<tr>@foreach($row as $v)<td>{{ is_int($v) && $v > 999 ? number_format($v) : $v }}</td>@endforeach</tr>@endforeach</tbody>
    </table>
@endif
<script>window.addEventListener('load', () => setTimeout(() => window.print(), 300));</script>
</body>
</html>
