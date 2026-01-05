<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 | Page Not Found</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0b1220;
            --panel: #0f172a;
            --accent: #38bdf8;
            --accent-2: #14b8a6;
            --ink: #e5e7eb;
            --muted: #94a3b8;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Space Grotesk", sans-serif;
            background:
                radial-gradient(900px 380px at 15% 0%, rgba(56, 189, 248, 0.18), transparent 60%),
                radial-gradient(700px 320px at 85% 5%, rgba(20, 184, 166, 0.18), transparent 55%),
                linear-gradient(160deg, #0b1220 0%, #111827 60%, #0f172a 100%);
            color: var(--ink);
            display: grid;
            place-items: center;
            padding: 32px 20px;
        }
        .wrap {
            width: min(900px, 100%);
            display: grid;
            grid-template-columns: minmax(200px, 300px) 1fr;
            gap: 28px;
            align-items: center;
            background: rgba(15, 23, 42, 0.8);
            border-radius: 24px;
            padding: 36px;
            border: 1px solid rgba(148, 163, 184, 0.15);
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.45);
        }
        .status {
            font-size: 88px;
            font-weight: 700;
            letter-spacing: -0.04em;
            color: transparent;
            background: linear-gradient(135deg, var(--accent), var(--accent-2));
            -webkit-background-clip: text;
            background-clip: text;
            line-height: 0.9;
        }
        h1 {
            margin: 0 0 12px;
            font-size: clamp(28px, 4vw, 40px);
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
        .map {
            margin-top: 18px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 12px;
        }
        .card {
            padding: 12px 14px;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.06);
            color: var(--muted);
            font-size: 13px;
        }
        .card strong { color: #7dd3fc; }
        @media (max-width: 720px) {
            .wrap { grid-template-columns: 1fr; text-align: center; }
            .actions { justify-content: center; }
        }
    </style>
</head>
<body>
    <main class="wrap">
        <div class="status">404</div>
        <section>
            <h1>Halaman tidak ditemukan.</h1>
            <p>URL yang kamu buka tidak ada atau sudah dipindah.</p>
            <div class="actions">
                <a class="btn btn-primary" href="{{ url('/') }}">Ke Beranda</a>
                <a class="btn btn-ghost" href="javascript:history.back()">Kembali</a>
            </div>
            <div class="map">
                <div class="card"><strong>Tip:</strong> Cek ejaan URL.</div>
                <div class="card"><strong>Status:</strong> 404</div>
                <div class="card"><strong>Waktu:</strong> {{ now()->format('d/m/Y H:i') }}</div>
            </div>
        </section>
    </main>
</body>
</html>
