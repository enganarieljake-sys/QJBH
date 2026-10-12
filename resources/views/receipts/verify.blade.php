<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt {{ $payment->receiptNumber() }} · Verified</title>
    <link rel="icon" type="image/png" href="{{ asset('images/queen-jackie-favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,200..1000;1,200..1000&display=swap" rel="stylesheet">
    <style>
        :root { color-scheme: light; --navy: #0b1f44; --muted: #64748b; --paid: #15803d; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px 16px; background: linear-gradient(180deg, #e8f1ff 0%, #f5f8fc 60%); color: var(--navy); font-family: 'Nunito', system-ui, -apple-system, Arial, sans-serif; font-optical-sizing: auto; }
        .card { width: 100%; max-width: 400px; overflow: hidden; border-radius: 22px; background: #fff; box-shadow: 0 18px 45px rgba(11, 31, 68, .1); text-align: center; }
        .top { padding: 28px 24px 20px; }
        .top img { height: 56px; }
        .badge { display: inline-flex; align-items: center; gap: 6px; margin-top: 16px; padding: 6px 14px; border-radius: 999px; background: #dcfce7; color: var(--paid); font-size: 13px; font-weight: 800; }
        .badge svg { width: 16px; height: 16px; }
        h1 { margin: 12px 0 4px; font-size: 20px; }
        .top p { margin: 0; color: var(--muted); font-size: 13px; }
        dl { display: grid; gap: 12px; margin: 0; padding: 20px 24px; border-top: 1px dashed #e2e8f0; text-align: left; }
        dl div { display: flex; justify-content: space-between; gap: 12px; }
        dt { color: var(--muted); font-size: 13px; }
        dd { margin: 0; font-size: 14px; font-weight: 700; }
        .amount { color: #a16207; }
        .bottom { padding: 18px 24px 24px; border-top: 1px dashed #e2e8f0; }
        .bottom a { display: block; padding: 13px; border-radius: 12px; background: var(--navy); color: #fff; font-weight: 600; text-decoration: none; }
        .bottom p { margin: 10px 0 0; color: var(--muted); font-size: 12px; }
    </style>
</head>
<body>
    <main class="card">
        <div class="top">
            <img src="{{ asset('images/queen-jackie-logo.png') }}" alt="Queen Jackie Boarding House">
            <div class="badge">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5L20 7"/></svg>
                Genuine receipt
            </div>
            <h1>{{ $payment->receiptNumber() }}</h1>
            <p>Issued by Queen Jackie Boarding House</p>
        </div>
        <dl>
            <div><dt>Date paid</dt><dd>{{ $payment->payment_date?->format('M j, Y') }}</dd></div>
            <div><dt>Amount</dt><dd class="amount">PHP {{ number_format($payment->amount, 2) }}</dd></div>
            <div><dt>Paid by</dt><dd>{{ $initials }}</dd></div>
        </dl>
        <div class="bottom">
            @guest
                <a href="{{ route('login') }}">Log in to view the full receipt</a>
                <p>Only the tenant who paid can open and download the full receipt.</p>
            @else
                <p>This receipt belongs to another tenant, so only its summary is shown.</p>
            @endguest
        </div>
    </main>
</body>
</html>
