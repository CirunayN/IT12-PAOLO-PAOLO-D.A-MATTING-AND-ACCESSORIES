<!DOCTYPE html>
<html>
<body style="font-family: Arial, Helvetica, sans-serif; background:#f5f5f5; padding:30px; color:#111827;">
    <div style="max-width:520px; margin:auto; background:#ffffff; border-radius:14px; padding:28px; border:1px solid #e5e7eb;">
        <h2 style="margin-top:0; color:#dc2626;">Employee Registration Confirmation</h2>

        <p>Hello {{ $employeeName }},</p>

        <p>An owner/admin is registering an employee account for you in the Paolo Paolo D.A. Matting &amp; Accessories system.</p>

        <p>Give this confirmation code to the owner/admin who is registering the account:</p>

        <div style="font-size:32px; font-weight:800; letter-spacing:8px; text-align:center; padding:20px; margin:24px 0; background:#f8fafc; border-radius:12px;">
            {{ $code }}
        </div>

        <p>This code expires in <strong>10 minutes</strong>. The employee account is not created until this code is verified.</p>

        <p style="margin-top:28px; font-size:12px; color:#6b7280;">
            Paolo Paolo D.A. Matting &amp; Accessories
        </p>
    </div>
</body>
</html>
