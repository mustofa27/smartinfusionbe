<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Device QR Codes</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            padding: 20px;
            background: #fff;
            color: #1e293b;
        }
        h1 {
            text-align: center;
            font-size: 20px;
            margin-bottom: 24px;
            padding-bottom: 12px;
            border-bottom: 2px solid #e2e8f0;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }
        .card {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
            text-align: center;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .card svg {
            max-width: 160px;
            height: auto;
            display: block;
            margin: 0 auto 8px;
        }
        .card .serial {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 2px;
        }
        .card .model {
            font-size: 12px;
            color: #64748b;
        }
        @media print {
            body { padding: 10px; }
            .grid { grid-template-columns: repeat(4, 1fr); gap: 8px; }
            .card { padding: 8px; }
            .card svg { max-width: 120px; }
            h1 { font-size: 16px; margin-bottom: 12px; }
        }
        @page {
            margin: 10mm;
        }
    </style>
</head>
<body>
    <h1>Smart Infusion Device QR Codes</h1>
    <div class="grid">
        @foreach ($qrCodes as $qr)
            <div class="card">
                {!! $qr['svg'] !!}
                <div class="serial">{{ $qr['serial_number'] }}</div>
                <div class="model">{{ $qr['model'] ?? '-' }}</div>
            </div>
        @endforeach
    </div>
    <script>
        window.onload = function() { window.print(); }
    </script>
</body>
</html>