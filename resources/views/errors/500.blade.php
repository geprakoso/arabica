<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 | Server Error</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #111827;
            --panel: #111827;
            --accent: #f97316;
            --accent-2: #ef4444;
            --ink: #e5e7eb;
            --muted: #a1a1aa;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Space Grotesk", sans-serif;
            color: var(--ink);
            background:
                radial-gradient(900px 380px at 0% 20%, rgba(239, 68, 68, 0.18), transparent 55%),
                radial-gradient(800px 360px at 90% 0%, rgba(249, 115, 22, 0.22), transparent 60%),
                linear-gradient(150deg, #0b0f19 0%, #111827 60%, #0f172a 100%);
            display: grid;
            place-items: center;
            padding: 32px 20px;
        }
        .panel {
            width: min(900px, 100%);
            border-radius: 26px;
            padding: 38px;
            background: rgba(17, 24, 39, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 30px 70px rgba(0, 0, 0, 0.5);
            position: relative;
            overflow: hidden;
        }
        .signal {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.35);
            color: #fecaca;
            font-size: 12px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            font-weight: 600;
        }
        h1 {
            margin: 16px 0 10px;
            font-size: clamp(30px, 4.5vw, 48px);
        }
        p {
            margin: 0 0 22px;
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
            color: #111827;
            box-shadow: 0 14px 30px rgba(239, 68, 68, 0.25);
        }
        .btn-ghost {
            background: rgba(255, 255, 255, 0.06);
            color: var(--ink);
            border: 1px solid rgba(255, 255, 255, 0.12);
        }
        .meta {
            margin-top: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
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
        .chip strong { color: #fdba74; }
        .glow {
            position: absolute;
            right: -80px;
            bottom: -80px;
            width: 240px;
            height: 240px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(249, 115, 22, 0.35), transparent 70%);
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <main class="panel">
        <span class="glow" aria-hidden="true"></span>
        <span class="signal">500 Server Error</span>
        <h1>Terjadi kesalahan di server.</h1>
        <p>Kami sedang memperbaikinya. Silakan coba lagi beberapa saat lagi.</p>
        <div class="actions">
            <a class="btn btn-primary" href="{{ url('/') }}">Ke Beranda</a>
            <a class="btn btn-ghost" href="javascript:location.reload()">Muat Ulang</a>
        </div>
        <div class="meta">
            <div class="chip"><strong>Waktu:</strong> {{ now()->format('d/m/Y H:i') }}</div>
            <div class="chip"><strong>Status:</strong> 500</div>
            <div class="chip"><strong>Info:</strong> Hubungi admin jika berulang.</div>
        </div>
    </main>
</body>
</html>
