<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Accepted — Set Up Your Portal</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f1f5f9; padding: 32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="max-width: 600px; width: 100%;">

                    {{-- Header --}}
                    <tr>
                        <td style="background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%); padding: 32px 40px; border-radius: 12px 12px 0 0;">
                            <h1 style="margin: 0; font-size: 24px; font-weight: 700; color: #ffffff; letter-spacing: -0.025em;">SecureCAT</h1>
                            <p style="margin: 4px 0 0; font-size: 13px; color: rgba(255,255,255,0.7); letter-spacing: 0.05em; text-transform: uppercase;">Computerized Admission &amp; Testing</p>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="background-color: #ffffff; padding: 40px;">

                            {{-- Acceptance Badge --}}
                            <table role="presentation" cellspacing="0" cellpadding="0" style="margin-bottom: 24px;">
                                <tr>
                                    <td style="background-color: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 8px; padding: 12px 20px;">
                                        <span style="font-size: 14px; font-weight: 600; color: #065f46;">&#10003; Application Accepted</span>
                                    </td>
                                </tr>
                            </table>

                            <h2 style="margin: 0 0 16px; font-size: 22px; font-weight: 700; color: #0f172a; line-height: 1.3;">
                                Congratulations, {{ $applicantName }}!
                            </h2>

                            <p style="margin: 0 0 16px; font-size: 15px; color: #475569; line-height: 1.65;">
                                We are pleased to inform you that your application has been <strong style="color: #065f46;">accepted</strong>. You are now ready to proceed with the next steps of the admission process.
                            </p>

                            @if($referenceNumber)
                            <table role="presentation" cellspacing="0" cellpadding="0" width="100%" style="margin-bottom: 24px;">
                                <tr>
                                    <td style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px 20px;">
                                        <p style="margin: 0 0 4px; font-size: 12px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">Reference Number</p>
                                        <p style="margin: 0; font-size: 18px; font-weight: 700; color: #1e40af; letter-spacing: 0.025em;">{{ $referenceNumber }}</p>
                                    </td>
                                </tr>
                            </table>
                            @endif

                            <p style="margin: 0 0 24px; font-size: 15px; color: #475569; line-height: 1.65;">
                                To access your <strong>Applicant Portal</strong> and track your admission progress, please set up your account password using the button below:
                            </p>

                            {{-- CTA Button --}}
                            <table role="presentation" cellspacing="0" cellpadding="0" width="100%">
                                <tr>
                                    <td align="center" style="padding: 8px 0 32px;">
                                        <a href="{{ $setupUrl }}" style="display: inline-block; padding: 14px 36px; background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%); color: #ffffff; font-size: 15px; font-weight: 600; text-decoration: none; border-radius: 8px;">
                                            Set Up Your Account
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 0 0 24px;">

                            <p style="margin: 0 0 8px; font-size: 13px; font-weight: 600; color: #64748b;">What you can do in the portal:</p>
                            <table role="presentation" cellspacing="0" cellpadding="0" style="margin: 0 0 24px;">
                                <tr><td style="padding: 4px 0; font-size: 13px; color: #64748b;">&#8226;&nbsp; Track your admission progress</td></tr>
                                <tr><td style="padding: 4px 0; font-size: 13px; color: #64748b;">&#8226;&nbsp; View exam schedules and results</td></tr>
                                <tr><td style="padding: 4px 0; font-size: 13px; color: #64748b;">&#8226;&nbsp; Access important announcements</td></tr>
                            </table>

                            <p style="margin: 0; font-size: 13px; color: #94a3b8; line-height: 1.5;">
                                This setup link expires in {{ $tokenExpiryHours }} hours. If you did not apply, you can safely ignore this email.
                            </p>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background-color: #f8fafc; padding: 24px 40px; border-radius: 0 0 12px 12px; border-top: 1px solid #e2e8f0;">
                            <p style="margin: 0; font-size: 12px; color: #94a3b8; line-height: 1.5; text-align: center;">
                                SecureCAT &mdash; Computerized Admission &amp; Testing<br>
                                This is an automated message. Please do not reply to this email.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
