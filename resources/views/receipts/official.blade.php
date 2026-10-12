<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Official Receipt {{ $payment->receiptNumber() }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/queen-jackie-favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,200..1000;1,200..1000&display=swap" rel="stylesheet">
    @vite(['resources/js/receipt.js'])
    <style>
        :root {
            color-scheme: light;
            --navy: #0b1f44;
            --blue: #0879ed;
            --gold: #a16207;
            --muted: #64748b;
            --line: #e2e8f0;
        }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; padding: 16px 16px 110px; background: linear-gradient(180deg, #e8f1ff 0%, #f5f8fc 55%); color: var(--navy); font-family: 'Nunito', system-ui, -apple-system, Arial, sans-serif; font-optical-sizing: auto; }
        .page { max-width: 460px; margin: 0 auto; }
        .topbar { display: grid; grid-template-columns: 40px 1fr 40px; align-items: center; margin-bottom: 16px; }
        .topbar h1 { margin: 0; font-size: 18px; font-weight: 700; text-align: center; }
        .icon-button { display: grid; width: 40px; height: 40px; place-items: center; border: 0; border-radius: 12px; background: transparent; color: var(--navy); cursor: pointer; text-decoration: none; }
        .icon-button:hover { background: rgba(11, 31, 68, .06); }
        .icon-button svg { width: 22px; height: 22px; }
        .icon-button--share { color: var(--blue); }

        .receipt { overflow: hidden; border-radius: 22px; background: #fff; box-shadow: 0 18px 45px rgba(11, 31, 68, .1); }
        .section { padding: 18px 20px; }
        .section + .section { border-top: 1px dashed var(--line); }
        .brand { display: flex; align-items: center; gap: 14px; }
        .brand img { width: 64px; height: 64px; flex: none; object-fit: contain; }
        .brand-title { margin: 0; font-size: 15px; font-weight: 700; }
        .brand-date { margin: 3px 0 0; color: var(--muted); font-size: 12px; }
        /* Rubber-stamp "PAID" over the details, see-through so the text below stays readable. */
        .details { position: relative; }
        .details dl { position: relative; z-index: 1; }
        .stamp { position: absolute; z-index: 0; top: 50%; right: 18px; display: grid; justify-items: center; padding: 6px 16px; border: 4px double rgba(21, 128, 61, .3); border-radius: 14px; color: rgba(21, 128, 61, .3); transform: translateY(-50%) rotate(-14deg); pointer-events: none; }
        .stamp span { font-size: 38px; font-weight: 900; letter-spacing: .14em; line-height: 1; }
        .stamp small { margin-top: 2px; font-size: 10px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
        dl { display: grid; grid-template-columns: 1fr 1fr; gap: 14px 18px; margin: 0; }
        dt { color: var(--muted); font-size: 11px; letter-spacing: .02em; }
        dd { margin: 3px 0 0; font-size: 14px; font-weight: 600; overflow-wrap: break-word; }
        .total { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; }
        .total span { font-size: 14px; font-weight: 600; }
        .total strong { color: var(--gold); font-size: 22px; font-weight: 800; }
        .balance { margin: 6px 0 0; color: var(--muted); font-size: 12px; text-align: right; }
        .qr { display: grid; justify-items: center; gap: 10px; padding: 22px 20px; background: #fbfcfe; }
        .qr svg { width: 190px; height: 190px; }
        .qr p { margin: 0; color: var(--muted); font-size: 12px; font-weight: 600; letter-spacing: .04em; }
        .note { margin: 14px 4px 0; color: var(--navy); font-size: 13px; line-height: 1.5; }
        .note b { color: var(--blue); }
        .signed { margin-top: 4px; color: var(--muted); font-size: 12px; text-align: center; }

        .actions { position: fixed; inset: auto 0 0 0; display: grid; grid-template-columns: 1fr 1fr; gap: 12px; padding: 14px 16px calc(14px + env(safe-area-inset-bottom)); background: #fff; box-shadow: 0 -10px 30px rgba(11, 31, 68, .08); }
        .actions button { min-height: 48px; border-radius: 12px; font: 600 15px inherit; cursor: pointer; }
        .actions .secondary { border: 1.5px solid var(--navy); background: #fff; color: var(--navy); }
        .actions .primary { border: 0; background: var(--navy); color: #fff; box-shadow: 0 10px 24px rgba(11, 31, 68, .25); }
        .actions .primary:disabled { opacity: .6; cursor: wait; }
        @media (min-width: 640px) {
            body { padding-bottom: 40px; }
            .actions { position: static; max-width: 460px; margin: 16px auto 0; padding: 0; background: transparent; box-shadow: none; }
        }
        @media print {
            body { padding: 0; background: #fff; }
            .topbar, .actions, .note { display: none; }
            .receipt { box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="topbar">
            <a href="{{ $backUrl }}" class="icon-button" aria-label="Back">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>
            </a>
            <h1>Official Receipt</h1>
            <button type="button" class="icon-button icon-button--share" data-receipt-share data-url="{{ \App\Support\ReceiptQr::url($payment) }}" data-title="Official Receipt {{ $payment->receiptNumber() }}" aria-label="Share receipt link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 13.5 6.8 4M15.4 6.5l-6.8 4"/></svg>
            </button>
        </div>

        <main class="receipt" data-receipt-card data-filename="{{ $payment->receiptNumber() }}">
            <section class="section brand">
                <img src="{{ asset('images/queen-jackie-logo.png') }}" alt="Queen Jackie Boarding House logo">
                <div>
                    <p class="brand-title">Queen Jackie Boarding House</p>
                    <p class="brand-date">{{ $payment->payment_date?->format('d M Y, l') }}</p>
                </div>
            </section>

            <section class="section details">
                <div class="stamp" aria-label="Paid">
                    <span>PAID</span>
                    <small>{{ $payment->payment_date?->format('M j, Y') }}</small>
                </div>
                <dl>
                    <div><dt>Receipt No.</dt><dd>{{ $payment->receiptNumber() }}</dd></div>
                    <div><dt>Received from</dt><dd>{{ $payment->tenant?->full_name ?? 'Tenant' }}</dd></div>
                    <div><dt>Room</dt><dd>{{ $payment->bill?->room ? 'Room '.$payment->bill->room->room_number : '—' }}</dd></div>
                    <div><dt>For bill</dt><dd>{{ $payment->bill?->billing_month?->format('F Y') ?? '—' }}</dd></div>
                    <div><dt>Payment method</dt><dd>{{ $payment->payment_method }}</dd></div>
                    <div><dt>Reference No.</dt><dd>{{ $payment->reference_number ?: 'None' }}</dd></div>
                </dl>
            </section>

            <section class="section">
                <div class="total">
                    <span>Amount paid</span>
                    <strong>PHP {{ number_format($payment->amount, 2) }}</strong>
                </div>
                @if ($payment->bill)
                    <p class="balance">Bill total PHP {{ number_format((float) $payment->bill->total_amount, 2) }} · Balance left PHP {{ number_format($payment->bill->balance, 2) }}</p>
                @endif
            </section>

            <section class="section qr">
                {!! $qr !!}
                <p>SCAN TO VIEW OR VERIFY</p>
                <p class="signed">Received by Ate Jackie · Queen Jackie Boarding House</p>
            </section>
        </main>

        <p class="note"><b>Note:</b> Keep this receipt. Scan its QR code in the app (My Payments, Scan receipt) to open it again anytime.</p>
    </div>

    <div class="actions">
        <button type="button" class="secondary" onclick="window.print()">Print</button>
        <button type="button" class="primary" data-receipt-download>Download</button>
    </div>
</body>
</html>
