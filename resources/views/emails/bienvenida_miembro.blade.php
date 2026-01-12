<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f3f4f6; padding: 20px; }
        .container { background-color: white; max-width: 600px; margin: 0 auto; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header { background-color: #111827; color: white; padding: 20px; text-align: center; }
        .content { padding: 30px; color: #374151; }

        /* Cajas de Información */
        .info-box { background: #f9fafb; border-left: 5px solid #2563EB; padding: 15px; margin-bottom: 20px; }
        .info-title { font-weight: bold; display: block; margin-bottom: 5px; color: #111827; text-transform: uppercase; font-size: 12px; }

        /* Esto hace que se respeten los saltos de línea que escriba el admin */
        .texto-admin { white-space: pre-line; color: #4b5563; }

        /* Botón WhatsApp */
        .btn-ws { background-color: #25D366; color: white; text-decoration: none; padding: 12px 20px; border-radius: 50px; display: inline-block; font-weight: bold; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>🏋️ {{ $gym->name }}</h2>
        </div>

        <div class="content">
            <h3>¡Hola, {{ explode(' ', trim($member->name))[0] }}! 👋</h3>
            <p>Tu membresía ha sido activada correctamente. Estamos felices de acompañarte en tu entrenamiento.</p>

            <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">

            <div class="info-box">
                <span class="info-title">🕒 Horarios de Atención</span>
                <div class="texto-admin">
                    {{ $gym->horarios ?? 'Consulta los horarios en recepción.' }}
                </div>
            </div>

            <div class="info-box" style="border-left-color: #F59E0B;">
                <span class="info-title">📜 Normas del Gimnasio</span>
                <div class="texto-admin">
                    {{ $gym->politicas ?? 'Por favor respeta las normas generales del establecimiento.' }}
                </div>
            </div>

            @if(!empty($gym->url_grupo_whatsapp))
                <div style="text-align: center; margin-top: 30px; background: #ecfdf5; padding: 20px; border-radius: 8px;">
                    <p style="margin-top: 0; color: #065f46; font-weight: bold;">📢 Únete a nuestra comunidad</p>
                    <p style="font-size: 14px;">Recibe avisos de festivos y promociones al instante.</p>
                    <a href="{{ $gym->url_grupo_whatsapp }}" class="btn-ws">📲 Unirme al Grupo de WhatsApp</a>
                </div>
            @endif

        </div>
        <div class="footer" style="text-align: center; padding: 20px; font-size: 12px; color: #9ca3af;">
            Enviado automáticamente por {{ $gym->name }}
        </div>
    </div>
</body>
</html>
