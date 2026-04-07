<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 | Access Denied</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-1: #0f172a;
            --bg-2: #1f2937;
            --accent: #f59e0b;
            --accent-2: #f97316;
            --ink: #e5e7eb;
            --muted: #9ca3af;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Space Grotesk", sans-serif;
            color: var(--ink);
            background:
                radial-gradient(900px 400px at 20% -10%, rgba(245, 158, 11, 0.2), transparent 60%),
                radial-gradient(700px 300px at 90% 10%, rgba(249, 115, 22, 0.18), transparent 55%),
                linear-gradient(135deg, var(--bg-1), var(--bg-2));
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 20px;
        }
        .card {
            width: min(900px, 100%);
            border-radius: 24px;
            padding: 40px;
            background: rgba(17, 24, 39, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.06);
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.4);
            position: relative;
            overflow: hidden;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 8px 16px;
            border-radius: 999px;
            background: rgba(245, 158, 11, 0.15);
            border: 1px solid rgba(245, 158, 11, 0.35);
            color: #fbbf24;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            font-size: 12px;
        }
        h1 {
            font-size: clamp(36px, 5vw, 56px);
            margin: 16px 0 8px;
            letter-spacing: -0.02em;
        }
        p {
            margin: 0 0 24px;
            font-size: 16px;
            color: var(--muted);
        }
        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }
        .btn {
            appearance: none;
            border: none;
            border-radius: 12px;
            padding: 12px 18px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--accent), var(--accent-2));
            color: #111827;
            box-shadow: 0 12px 24px rgba(249, 115, 22, 0.25);
        }
        .btn-ghost {
            background: rgba(255, 255, 255, 0.06);
            color: var(--ink);
            border: 1px solid rgba(255, 255, 255, 0.12);
        }
        .btn:hover { transform: translateY(-1px); }
        .grid {
            margin-top: 28px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
        }
        .tile {
            padding: 16px;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.06);
            font-size: 13px;
            color: var(--muted);
        }
        .tile strong { color: #fbbf24; }
        .glow {
            position: absolute;
            width: 220px;
            height: 220px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(245, 158, 11, 0.35), transparent 70%);
            right: -60px;
            top: -60px;
            filter: blur(2px);
            opacity: 0.8;
        }
        @media (max-width: 640px) {
            .card { padding: 28px; }
        }
    </style>
</head>
<body>
    <main class="card">
        <span class="glow" aria-hidden="true"></span>
        <span class="badge">403 Access Denied</span>
        <h1>Oops, kamu belum punya akses.</h1>
        <p>Halaman ini dilindungi. Coba login dengan akun yang memiliki izin atau kembali ke halaman utama.</p>
        <div class="actions">
            <a class="btn btn-primary" href="{{ url('/') }}">Kembali ke Beranda</a>
            <a class="btn btn-ghost" href="javascript:history.back()">Kembali</a>
        </div>
        <div class="grid">
            <div class="tile"><strong>Tips:</strong> Pastikan role kamu sudah sesuai.</div>
            <div class="tile"><strong>Butuh bantuan?</strong> Hubungi admin sistem.</div>
            <div class="tile"><strong>Kode:</strong> 403</div>
        </div>
    </main>
</body>
</html>
