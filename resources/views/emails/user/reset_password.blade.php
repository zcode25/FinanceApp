<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
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
                        <td style="padding: 40px 40px 10px 40px; text-align: center;">
                            <h1 style="color: #1e293b; font-size: 24px; margin: 0; font-weight: 700;">🔐 Password Reset
                                Request</h1>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 20px 40px 20px 40px; color: #475569; font-size: 16px; line-height: 1.6;">
                            Hi <strong style="color: #1e293b;">{{ $user->name }}</strong>,<br><br>
                            You are receiving this email because we received a password reset request for your account.
                            If you did not request a password reset, no further action is required.
                        </td>
                    </tr>

                    <!-- CTA -->
                    <tr>
                        <td style="padding: 10px 40px 30px 40px; text-align: center;">
                            <a href="{{ $url }}"
                                style="background-color: #1e293b; color: #ffffff; padding: 14px 32px; border-radius: 6px; text-decoration: none; font-weight: 600; display: inline-block; font-size: 16px;">Reset
                                My Password</a>
                        </td>
                    </tr>

                    <!-- Info Box -->
                    <tr>
                        <td style="padding: 0 40px 30px 40px;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0"
                                style="background-color: #fff7ed; border-radius: 8px; border: 1px solid #fed7aa;">
                                <tr>
                                    <td style="padding: 15px; font-size: 12px; line-height: 1.5; color: #9a3412;">
                                        <strong style="color: #7c2d12;">⚠️ Security Notice</strong><br>
                                        This password reset link will expire in {{ $count }} minutes. For your
                                        security, please do not share this link with anyone.
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer Content -->
                    <tr>
                        <td style="padding: 0 40px 40px 40px; color: #475569; font-size: 15px; line-height: 1.6;">
                            If you're having trouble, please contact our support team.<br><br>
                            Best regards,<br>
                            <strong style="color: #1e293b;">{{ config('app.name') }} Team</strong>
                        </td>
                    </tr>

                    <!-- Subcopy -->
                    <tr>
                        <td style="padding: 20px 40px; border-top: 1px solid #f1f5f9; background-color: #fafafa;">
                            <p style="color: #94a3b8; font-size: 12px; margin: 0; line-height: 1.5;">
                                If you're having trouble clicking the "Reset My Password" button, copy and paste the URL
                                below into your web browser:<br>
                                <a href="{{ $url }}"
                                    style="color: #3b82f6; text-decoration: underline; word-break: break-all;">{{ $url }}</a>
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
