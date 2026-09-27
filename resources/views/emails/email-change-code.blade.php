<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Change Verification</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; background:#f4f4f5; color:#18181b; padding:24px;">
    <div style="max-width:560px; margin:0 auto; background:white; border-radius:14px; padding:28px; border:1px solid #e4e4e7;">
        <h2 style="margin-top:0;">Paolo Paolo D.A. Matting &amp; Accessories</h2>

        <p>Hello {{ $accountName }},</p>

        <p>A request was made to change your system account email to:</p>

        <p><strong>{{ $newEmail }}</strong></p>

        <p>Your 6-digit verification code is:</p>

        <div style="font-size:32px; font-weight:800; letter-spacing:8px; text-align:center; padding:18px; background:#f4f4f5; border-radius:12px;">
            {{ $code }}
        </div>

        <p>This code expires in <strong>10 minutes</strong>.</p>

        <p>If you did not request this email change, do not share this code and leave the request unverified.</p>
    </div>
</body>
</html>
