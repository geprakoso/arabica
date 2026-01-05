<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>429 | Too Many Requests</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0b1220;
            --panel: rgba(15, 23, 42, 0.85);
            --accent: #facc15;
            --accent-2: #f97316;
            --ink: #e5e7eb;
            --muted: #94a3b8;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Space Grotesk", sans-serif;
            background:
                radial-gradient(900px 380px at 10% 0%, rgba(250, 204, 21, 0.18), transparent 60%),
                radial-gradient(700px 320px at 90% 10%, rgba(249, 115, 22, 0.2), transparent 55%),
                linear-gradient(160deg, #0b1220 0%, #111827 60%, #0f172a 100%);
            color: var(--ink);
            display: grid;
            place-items: center;
            padding: 32px 20px;
        }
        .panel {
            width: min(880px, 100%);
            border-radius: 24px;
            padding: 36px;
            background: var(--panel);
            border: 1px solid rgba(148, 163, 184, 0.12);
            box-shadow: 0 28px 60px rgba(0, 0, 0, 0.4);
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(250, 204, 21, 0.15);
            border: 1px solid rgba(250, 204, 21, 0.35);
            color: #fef08a;
            font-size: 12px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            font-weight: 600;
        }
        h1 {
            margin: 16px 0 10px;
            font-size: clamp(30px, 4.5vw, 46px);
        }
        p {
            margin: 0 0 20px;
            color: var(--muted);
        }
        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .btn {
            appearance: none;
            border-radius: 12px;
            padding: 12px 18px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--accent), var(--accent-2));
            color: #0b1220;
        }
        .btn-ghost {
            background: rgba(255, 255, 255, 0.06);
            color: var(--ink);
            border: 1px solid rgba(255, 255, 255, 0.12);
        }
        .meta {
            margin-top: 18px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 12px;
        }
        .chip {
            padding: 12px 14px;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.06);
            font-size: 13px;
            color: var(--muted);
        }
        .chip strong { color: #fde68a; }
    </style>
</head>
<body>
    <main class="panel">
        <span class="badge">429 Too Many Requests</span>
        <h1>Terlalu banyak permintaan.</h1>
        <p>Silakan tunggu beberapa saat sebelum mencoba lagi.</p>
        <div class="actions">
            <a class="btn btn-primary" href="javascript:location.reload()">Coba Lagi</a>
            <a class="btn btn-ghost" href="{{ url('/') }}">Ke Beranda</a>
        </div>
        <div class="meta">
            <div class="chip"><strong>Status:</strong> 429</div>
            <div class="chip"><strong>Waktu:</strong> {{ now()->format('d/m/Y H:i') }}</div>
            <div class="chip"><strong>Tip:</strong> Kurangi frekuensi request.</div>
        </div>
    </main>
</body>
</html>
