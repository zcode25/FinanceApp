<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Order Received</title>
</head>

<body
    style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f7f9; margin: 0; padding: 0;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f4f7f9; padding: 20px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" border="0"
                    style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
                    <!-- Header -->
                    <tr>
                        <td style="padding: 40px 40px 20px 40px; text-align: center;">
                            <h1 style="color: #1e293b; font-size: 24px; margin: 0; font-weight: 700;">💰 New Order
                                Received!</h1>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 0 40px 20px 40px; color: #475569; font-size: 16px; line-height: 1.6;">
                            Hi! A user has just completed a premium subscription on <strong
                                style="color: #1e293b;">{{ config('app.name') }}</strong>.
                        </td>
                    </tr>

                    <!-- Order Box -->
                    <tr>
                        <td style="padding: 0 40px 30px 40px;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0"
                                style="background-color: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td
                                                    style="padding-bottom: 12px; color: #64748b; font-size: 12px; font-weight: 700;">
                                                    Order Summary</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 4px 0; color: #1e293b; font-size: 12px;">
                                                    <strong style="font-weight: 600;">Customer:</strong> <span
                                                        style="color: #475569;">{{ $transaction->user->name }}
                                                        ({{ $transaction->user->email }})</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 4px 0; color: #1e293b; font-size: 12px;">
                                                    <strong style="font-weight: 600;">Plan:</strong>
                                                    <span
                                                        style="background-color: #dcfce7; color: #166534; padding: 2px 10px; border-radius: 12px; font-size: 12px; font-weight: 700; display: inline-block;">{{ $transaction->plan->name ?? 'Premium' }}</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 4px 0; color: #1e293b; font-size: 12px;">
                                                    <strong style="font-weight: 600;">Amount:</strong> <span
                                                        style="color: #059669; font-weight: 700; font-size: 12px;">Rp
                                                        {{ number_format($transaction->amount, 0, ',', '.') }}</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 4px 0; color: #1e293b; font-size: 12px;">
                                                    <strong style="font-weight: 600;">Method:</strong> <span
                                                        style="color: #475569; font-size: 12px;">{{ str_replace('_', ' ', ucwords(strtolower($transaction->payment_type), '_')) }}</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 4px 0; color: #1e293b; font-size: 12px;">
                                                    <strong style="font-weight: 600;">Order ID:</strong> <span
                                                        style="color: #475569; font-size: 12px;">{{ $transaction->external_id }}</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- CTA -->
                    <tr>
                        <td style="padding: 0 40px 40px 40px; text-align: center;">
                            <p style="color: #475569; font-size: 12px; margin-bottom: 25px;">Check the transaction
                                details in your admin dashboard.</p>
                            <a href="{{ config('app.url') }}/vibe-hq/subscription-transactions"
                                style="background-color: #1e293b; color: #ffffff; padding: 14px 28px; border-radius: 6px; text-decoration: none; font-weight: 600; display: inline-block; font-size: 16px;">View
                                Transaction</a>
                        </td>
                    </tr>

                    <!-- Footer Text -->
                    <tr>
                        <td style="padding: 0 40px 40px 40px; border-top: 1px solid #f1f5f9; text-align: center;">
                            <p style="color: #64748b; font-size: 12px; margin-top: 20px; font-weight: 600;">
                                Keep up the great work!<br>
                                <span style="color: #94a3b8;">{{ config('app.name') }} Team</span>
                            </p>
                        </td>
                    </tr>
                </table>
                <!-- Copyright -->
                <p style="color: #94a3b8; font-size: 12px; margin-top: 20px; text-align: center;">
                    &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                </p>
            </td>
        </tr>
    </table>
</body>

</html>
