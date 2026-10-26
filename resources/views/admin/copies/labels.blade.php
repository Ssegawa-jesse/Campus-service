<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>QR labels</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 10mm; }
        .sheet { display: grid; grid-template-columns: repeat(4, 1fr); gap: 4mm; }
        .label { border: 1px dashed #999; padding: 3mm; text-align: center; page-break-inside: avoid; font-size: 9pt; }
        .label .title { font-weight: bold; height: 2.4em; overflow: hidden; }
        .label code { font-size: 10pt; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
<p class="no-print"><button onclick="window.print()">Print labels</button> {{ $copies->count() }} labels</p>
<div class="sheet">
    @foreach($copies as $copy)
        <div class="label">
            <div class="title">{{ Str::limit($copy->book->title, 50) }}</div>
            {!! \App\Support\Qr::svg($copy->code, 110, 1) !!}
            <div><code>{{ $copy->code }}</code></div>
            <div>{{ $copy->shelf_location }}</div>
        </div>
    @endforeach
</div>
</body>
</html>
