<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>School Activation OTP</title>
</head>
<body style="font-family: Arial, sans-serif; color: #0f172a; line-height: 1.4;">
    <h2 style="margin-bottom: 8px;">School Activation Verification</h2>
    <p style="margin: 0 0 12px 0;">Hello {{ $schoolName }},</p>
    <p style="margin: 0 0 12px 0;">Use this OTP code to continue activation:</p>
    <p style="font-size: 24px; font-weight: 700; letter-spacing: 4px; margin: 0 0 12px 0;">{{ $otp }}</p>
    <p style="margin: 0 0 12px 0;">This code expires in {{ $expiryMinutes }} minutes.</p>
    <p style="margin: 0; color: #475569;">If you did not request this, you can ignore this email.</p>
</body>
</html>
