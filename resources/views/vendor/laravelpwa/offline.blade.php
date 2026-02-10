<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0284c7">
    <title>Offline - Arabica System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f0f4f8 0%, #d9e2ec 100%);
            color: #334155;
        }

        .container {
            text-align: center;
            padding: 2rem;
            max-width: 480px;
        }

        .icon {
            width: 120px;
            height: 120px;
            margin: 0 auto 2rem;
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 40px rgba(2, 132, 199, 0.3);
        }

        .icon svg {
            width: 64px;
            height: 64px;
            color: white;
        }

        h1 {
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: #1e293b;
        }

        p {
            font-size: 1rem;
            line-height: 1.6;
            color: #64748b;
            margin-bottom: 1.5rem;
        }

        .retry-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 4px 14px rgba(2, 132, 199, 0.3);
        }

        .retry-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(2, 132, 199, 0.4);
        }

        .retry-btn:active {
            transform: translateY(0);
        }

        .retry-btn svg {
            width: 20px;
            height: 20px;
        }

        .tips {
            margin-top: 2rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 12px;
            font-size: 0.875rem;
            color: #475569;
        }

        .tips strong {
            display: block;
            margin-bottom: 0.5rem;
            color: #334155;
        }

        @media (prefers-color-scheme: dark) {
            body {
                background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
                color: #e2e8f0;
            }

            h1 {
                color: #f1f5f9;
            }

            p {
                color: #94a3b8;
            }

            .tips {
                background: rgba(30, 41, 59, 0.7);
                color: #94a3b8;
            }

            .tips strong {
                color: #e2e8f0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 017.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 011.06 0z" />
            </svg>
        </div>

        <h1>Tidak Ada Koneksi Internet</h1>
        <p>Sepertinya Anda sedang offline. Pastikan perangkat terhubung ke jaringan untuk mengakses Arabica System.</p>

        <button class="retry-btn" onclick="window.location.reload()">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
            </svg>
            Coba Lagi
        </button>

        <div class="tips">
            <strong>Tips:</strong>
            Periksa koneksi WiFi atau data seluler Anda, lalu tekan "Coba Lagi".
        </div>
    </div>
</body>
</html>