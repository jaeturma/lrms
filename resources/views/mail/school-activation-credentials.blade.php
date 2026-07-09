<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your LRMS Account Credentials</title>
</head>
<body style="font-family: Arial, sans-serif; color: #0f172a; line-height: 1.4;">
    <h2 style="margin-bottom: 8px;">Account Activated</h2>
    <p style="margin: 0 0 12px 0;">Hello {{ $schoolName }},</p>
    <p style="margin: 0 0 12px 0;">Your school account has been activated. Use the credentials below to sign in:</p>
    <p style="margin: 0 0 4px 0;"><strong>Email:</strong> {{ $email }}</p>
    <p style="font-size: 20px; font-weight: 700; letter-spacing: 1px; margin: 0 0 12px 0;"><strong>Password:</strong> {{ $password }}</p>
    <p style="margin: 0 0 12px 0; color: #475569;">For your security, please sign in and consider keeping this password somewhere safe. It will not be shown or emailed again.</p>
    <p style="margin: 0; color: #475569;">If you did not request this activation, please contact your division office immediately.</p>
</body>
</html>
