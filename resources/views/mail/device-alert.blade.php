<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion sur un nouvel appareil</title>
</head>

<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">

    <div style="max-width:560px;margin:24px auto;background:#ffffff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden;">

        <div style="background:#4f46e5;padding:20px 24px;color:#ffffff;">
            <h2 style="margin:0;font-size:18px;">Gedivepro &middot; Sécurité</h2>
        </div>

        <div style="padding:24px;">
            <p style="margin:0 0 12px;font-size:15px;">
                Une connexion <strong>hors SSO</strong> a été tentée depuis un nouvel appareil&nbsp;:
            </p>

            <table style="width:100%;border-collapse:collapse;font-size:14px;margin:0 0 16px;">
                <tr>
                    <td style="padding:6px 0;color:#6b7280;width:130px;">Plateforme</td>
                    <td style="padding:6px 0;">{{ $match->platform ?? '—' }}</td>
                </tr>
                <tr>
                    <td style="padding:6px 0;color:#6b7280;">Appareil</td>
                    <td style="padding:6px 0;">{{ $match->device ?? '—' }}</td>
                </tr>
                <tr>
                    <td style="padding:6px 0;color:#6b7280;">Navigateur</td>
                    <td style="padding:6px 0;">{{ $match->matching_regex ?? '—' }}</td>
                </tr>
                <tr>
                    <td style="padding:6px 0;color:#6b7280;">Adresse IP</td>
                    <td style="padding:6px 0;">{{ $match->ip_request ?? '—' }}</td>
                </tr>
            </table>

            <p style="margin:0 0 8px;font-size:15px;">
                Si vous êtes à l'origine de cette connexion, saisissez le code de vérification
                ci-dessous pour vous connecter&nbsp;:
            </p>

            <div style="text-align:center;margin:16px 0;">
                <span style="display:inline-block;padding:12px 28px;font-size:24px;letter-spacing:4px;font-weight:bold;background:#eef2ff;color:#4338ca;border-radius:10px;border:1px solid #c7d2fe;">
                    {{ $match->match_token }}
                </span>
            </div>

            <p style="margin:16px 0 0;font-size:13px;color:#6b7280;">
                Ce code est valable 15&nbsp;minutes. Si vous n'êtes pas à l'origine de cette
                connexion, ignorez cet e-mail et changez votre mot de passe.
            </p>
        </div>

        <div style="background:#f9fafb;padding:14px 24px;border-top:1px solid #e5e7eb;font-size:12px;color:#9ca3af;text-align:center;">
            &copy; {{ date('Y') }} Gedivepro — message automatique, ne pas répondre.
        </div>
    </div>

</body>

</html>
