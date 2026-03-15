<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            padding: 10px;
        }
        .token-card {
            border: 1px dashed #999;
            border-radius: 6px;
            padding: 10px 8px;
            text-align: center;
            width: 100%;
        }
        .token-card .label { font-size: 9px; color: #666; margin-bottom: 4px; }
        .token-card .code  { font-size: 22px; font-weight: bold; letter-spacing: 4px; color: #1a1a1a; }
        .token-card .valid { font-size: 8px; color: #888; margin-top: 5px; }
        .token-card .wifi  { font-size: 9px; color: #444; margin-top: 2px; }
    </style>
</head>
<body>
    <div class="grid">
        @foreach($tokens as $token)
        <div class="token-card">
            <div class="label">🌐 WiFi Access Token</div>
            <div class="code">{{ $token->code }}</div>
            <div class="wifi">Masukan kode di halaman login WiFi</div>
            <div class="valid">
                Berlaku hingga:<br>
                {{ $token->valid_until->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB
            </div>
        </div>
        @endforeach
    </div>
</body>
</html>