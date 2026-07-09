<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Password Reset Code</title>
</head>
<body style="font-family: Arial, sans-serif; color: #0f172a; line-height: 1.4;">
    <h2 style="margin-bottom: 8px;">Password Reset Request</h2>
    <p style="margin: 0 0 12px 0;">Hello {{ $name }},</p>
    <p style="margin: 0 0 12px 0;">Use this OTP code to reset your password:</p>
    <p style="font-size: 24px; font-weight: 700; letter-spacing: 4px; margin: 0 0 12px 0;">{{ $otp }}</p>
    <p style="margin: 0 0 12px 0;">This code expires in {{ $expiryMinutes }} minutes.</p>
    <p style="margin: 0; color: #475569;">If you did not request a password reset, you can ignore this email.</p>
</body>
</html>
