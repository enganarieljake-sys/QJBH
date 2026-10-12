Welcome home, {{ $firstName }}!

Dear {{ $tenant->full_name }},

Thank you for choosing Queen Jackie Boarding House. Your tenant account is ready.

YOUR TENANCY DETAILS
@foreach ($details as $label => $value)
{{ $label }}: {{ $value }}
@endforeach

Set up your account (link expires {{ $expiresAt }}):
{{ $setupUrl }}

Getting started:
1. Set your password using the link above.
2. Check your bills, due dates, and balance anytime.
3. Pay by scanning the GCash QR in the portal, then upload your receipt.

We will never ask for your password by text, call, or chat. If you weren't expecting this email, you can ignore it.

Queen Jackie Boarding House
