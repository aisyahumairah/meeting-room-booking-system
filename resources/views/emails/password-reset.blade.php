<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Password Reset - MRBS</title>
</head>

<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #696cff;">Password Reset Request</h2>

        <p>Hello {{ $user->name }},</p>

        <p>We received a request to reset your password for your MRBS account.</p>

        <p>Click the button below to reset your password:</p>

        <p style="text-align: center; margin: 30px 0;">
            <a href="{{ $resetUrl }}"
                style="background-color: #696cff; color: white; padding: 12px 30px;
                      text-decoration: none; border-radius: 5px; display: inline-block;">
                Reset Password
            </a>
        </p>

        <p>This link will expire in 30 minutes.</p>

        <p>If you didn't request a password reset, please ignore this email.</p>

        <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">

        <p style="color: #666; font-size: 12px;">
            This is an automated message from the Meeting Room Booking System (MRBS).
        </p>
    </div>
</body>

</html>
