<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Member Joined</title>
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
                            <h1 style="color: #1e293b; font-size: 24px; margin: 0; font-weight: 700;">🚀 New Member
                                Joined!</h1>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 0 40px 20px 40px; color: #475569; font-size: 16px; line-height: 1.6;">
                            Great news! A new user has just created an account on <strong
                                style="color: #1e293b;">{{ config('app.name') }}</strong>.
                        </td>
                    </tr>

                    <!-- User Box -->
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
                                                    User Information</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 4px 0; color: #1e293b; font-size: 12px;">
                                                    <strong style="font-weight: 600;">Name:</strong> <span
                                                        style="color: #475569;">{{ $user->name }}</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 4px 0; color: #1e293b; font-size: 12px;">
                                                    <strong style="font-weight: 600;">Email:</strong> <span
                                                        style="color: #475569;">{{ $user->email }}</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 4px 0; color: #1e293b; font-size: 12px;">
                                                    <strong style="font-weight: 600;">Joined At:</strong> <span
                                                        style="color: #475569;">{{ $user->created_at->format('l, j F Y - H:i') }}</span>
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
                            <p style="color: #475569; font-size: 12px; margin-bottom: 25px;">You can manage this user
                                and view more details in the admin dashboard.</p>
                            <a href="{{ config('app.url') }}/vibe-hq/users"
                                style="background-color: #16a34a; color: #ffffff; padding: 14px 28px; border-radius: 6px; text-decoration: none; font-weight: 600; display: inline-block; font-size: 16px;">Go
                                to Admin Panel</a>
                        </td>
                    </tr>

                    <!-- Footer Text -->
                    <tr>
                        <td style="padding: 0 40px 40px 40px; border-top: 1px solid #f1f5f9; text-align: center;">
                            <p style="color: #94a3b8; font-size: 12px; margin-top: 20px;">
                                Happy managing!<br>
                                <strong style="color: #64748b;">{{ config('app.name') }} Team</strong>
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
