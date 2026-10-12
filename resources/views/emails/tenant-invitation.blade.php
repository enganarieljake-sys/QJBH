<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <title>Welcome to Queen Jackie Boarding House</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f1ea;font-family:'Nunito',Arial,Helvetica,sans-serif;color:#24211d;">
    <div style="display:none;max-height:0;overflow:hidden;">Your room is ready, {{ $firstName }}. Set up your tenant account to see your bills, pay by QR, and track repairs.</div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f4f1ea;">
        <tr>
            <td align="center" style="padding:24px 12px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;background-color:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e7e1d4;">

                    {{-- Header with logo --}}
                    <tr>
                        <td align="center" style="background-color:#24211d;padding:28px 24px 22px;">
                            <img src="{{ $message->embed(resource_path('images/email-logo.png')) }}" width="96" height="96" alt="Queen Jackie Boarding House" style="display:block;width:96px;height:96px;border:0;background-color:#ffffff;border-radius:48px;padding:6px;">
                            <p style="margin:14px 0 0;color:#e8b931;font-size:12px;font-weight:bold;letter-spacing:2px;text-transform:uppercase;">Queen Jackie Boarding House</p>
                        </td>
                    </tr>

                    {{-- Hero --}}
                    <tr>
                        <td style="background-color:#0754d5;padding:28px 32px;">
                            <p style="margin:0;color:#cfe0ff;font-size:13px;font-weight:bold;letter-spacing:1px;text-transform:uppercase;">You're invited</p>
                            <h1 style="margin:6px 0 0;color:#ffffff;font-size:26px;line-height:1.25;">Welcome home, {{ $firstName }}!</h1>
                            <p style="margin:10px 0 0;color:#e6efff;font-size:15px;line-height:1.6;">Your tenant account is ready. Set your password to start using the tenant portal.</p>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:28px 32px 8px;">
                            <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">Dear {{ $tenant->full_name }},</p>
                            <p style="margin:0 0 20px;font-size:15px;line-height:1.6;">Thank you for choosing Queen Jackie Boarding House. Here are the details of your stay. Please check that everything is correct.</p>

                            {{-- Tenancy details --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #e7e1d4;border-radius:12px;border-collapse:separate;overflow:hidden;">
                                <tr>
                                    <td colspan="2" style="background-color:#faf7f0;padding:12px 16px;border-bottom:1px solid #e7e1d4;font-size:13px;font-weight:bold;letter-spacing:1px;text-transform:uppercase;color:#6b6252;">Your tenancy details</td>
                                </tr>
                                @foreach ($details as $label => $value)
                                    <tr>
                                        <td style="padding:12px 16px;font-size:14px;color:#6b6252;width:42%;{{ $loop->last ? '' : 'border-bottom:1px solid #f0ebe0;' }}">{{ $label }}</td>
                                        <td style="padding:12px 16px;font-size:14px;font-weight:bold;color:#24211d;text-align:right;{{ $loop->last ? '' : 'border-bottom:1px solid #f0ebe0;' }}">{{ $value }}</td>
                                    </tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>

                    {{-- Call to action --}}
                    <tr>
                        <td align="center" style="padding:24px 32px 8px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center" style="background-color:#0754d5;border-radius:999px;">
                                        <a href="{{ $setupUrl }}" style="display:inline-block;padding:15px 34px;color:#ffffff;font-size:16px;font-weight:bold;text-decoration:none;border-radius:999px;">Set up my account</a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:12px 0 0;font-size:12px;color:#6b6252;">This link expires on {{ $expiresAt }}.</p>
                        </td>
                    </tr>

                    {{-- Steps --}}
                    <tr>
                        <td style="padding:24px 32px 8px;">
                            <p style="margin:0 0 12px;font-size:16px;font-weight:bold;">Getting started is easy</p>
                            @foreach ([
                                ['Set your password', 'Tap the button above and choose a password for your account.'],
                                ['Check your bills', 'See your monthly rent, utilities, due dates, and balance anytime.'],
                                ['Pay and send your receipt', 'Scan the GCash QR in the portal, pay, then upload a screenshot of your receipt.'],
                            ] as $i => [$title, $text])
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:12px;">
                                    <tr>
                                        <td valign="top" style="width:36px;">
                                            <div style="width:28px;height:28px;line-height:28px;border-radius:14px;background-color:#e8f0ff;color:#0754d5;font-size:14px;font-weight:bold;text-align:center;">{{ $i + 1 }}</div>
                                        </td>
                                        <td valign="top" style="padding-left:6px;">
                                            <p style="margin:0;font-size:14px;font-weight:bold;">{{ $title }}</p>
                                            <p style="margin:2px 0 0;font-size:13px;line-height:1.5;color:#6b6252;">{{ $text }}</p>
                                        </td>
                                    </tr>
                                </table>
                            @endforeach
                            <p style="margin:4px 0 0;font-size:13px;line-height:1.5;color:#6b6252;">You can also read announcements on the bulletin board and file maintenance requests from your phone.</p>
                        </td>
                    </tr>

                    {{-- Security note --}}
                    <tr>
                        <td style="padding:20px 32px 28px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#fff8e6;border:1px solid #f3dfa5;border-radius:12px;">
                                <tr>
                                    <td style="padding:14px 16px;font-size:13px;line-height:1.55;color:#5c4a12;">
                                        <strong>Keep your account safe.</strong> We will never ask for your password by text, call, or chat. If you weren't expecting this email, you can ignore it.
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:16px 0 0;font-size:12px;line-height:1.5;color:#8a8170;">Button not working? Copy and paste this link into your browser:<br><a href="{{ $setupUrl }}" style="color:#0754d5;word-break:break-all;">{{ $setupUrl }}</a></p>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td align="center" style="background-color:#24211d;padding:20px 24px;">
                            <p style="margin:0;color:#e8b931;font-size:13px;font-weight:bold;">Queen Jackie Boarding House</p>
                            <p style="margin:6px 0 0;color:#bdb4a2;font-size:12px;line-height:1.5;">Questions about your stay? Reply to this email or message the admin through the tenant portal.</p>
                        </td>
                    </tr>
                </table>
                <p style="margin:16px 0 0;font-size:11px;color:#8a8170;">This is an automated message for {{ $user->email }}.</p>
            </td>
        </tr>
    </table>
</body>
</html>
