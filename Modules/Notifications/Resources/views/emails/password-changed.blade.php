<div style="font-family: Inter, system-ui, sans-serif; background-color:#f8fafc; padding:40px 0;">
    <div style="max-width:600px; margin:0 auto; background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden;">

        <!-- HEADER -->
        <div style="background:#0a1628; padding:24px; text-align:center;">
            <h1 style="color:#ffffff; margin:0; font-size:20px; font-weight:600;">
                Password Changed
            </h1>
        </div>

        <!-- BODY -->
        <div style="padding:32px; color:#1e293b;">

            <p style="margin:0 0 16px; color:#64748b;">
                Hello,
            </p>

            <p style="margin:0 0 16px;">
                Your password was successfully changed for account:
            </p>

            <div style="background:#f1f5f9; border:1px solid #e2e8f0; padding:12px 16px; border-radius:8px; font-weight:600; color:#0a1628; margin-bottom:20px;">
                {{ $userEmail }}
            </div>

            <div style="background:#f0fdf4; border:1px solid #16803c; padding:16px; border-radius:8px; margin-bottom:20px;">
                <p style="margin:0; color:#15803d; font-size:14px;">
                    ✔ If this was you, no further action is needed.
                </p>
            </div>

            <div style="background:#fef2f2; border:1px solid #dc2626; padding:16px; border-radius:8px;">
                <p style="margin:0; color:#b91c1c; font-size:14px;">
                    ⚠ If this was NOT you, please contact support immediately.
                </p>
            </div>

        </div>

        <!-- FOOTER -->
        <div style="padding:16px; text-align:center; font-size:12px; color:#94a3b8; border-top:1px solid #e2e8f0;">
            NTI Security Team
        </div>

    </div>
</div>
