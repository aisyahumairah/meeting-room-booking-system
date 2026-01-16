<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Welcome to MRBS</title>
</head>

<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #696cff;">Welcome to MRBS, {{ $user->name }}!</h2>

        <p>Your account has been created for the Meeting Room Booking System.</p>

        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <p style="margin: 0;"><strong>Your Login Credentials:</strong></p>
            <ul style="list-style-type: none; padding-left: 0;">
                <li><strong>Email:</strong> {{ $user->email }}</li>
                <li><strong>Temporary Password:</strong> {{ $tempPassword }}</li>
            </ul>
        </div>

        <p style="text-align: center; margin: 30px 0;">
            <a href="{{ $loginUrl }}"
                style="background-color: #696cff; color: white; padding: 12px 30px;
                      text-decoration: none; border-radius: 5px; display: inline-block;">
                Login Now
            </a>
        </p>

        <p style="background-color: #fff3cd; color: #856404; padding: 10px; border-radius: 5px; font-size: 14px;">
            <strong>Important:</strong> For security, you will be required to change your password upon first login.
        </p>

        <p>If you have any questions, please contact your system administrator.</p>

        <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">

        <p style="color: #666; font-size: 12px;">
            This is an automated message from the Meeting Room Booking System ({{ config('app.name') }}).
        </p>
    </div>
</body>

</html>
