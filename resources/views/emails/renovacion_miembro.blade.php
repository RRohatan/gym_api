<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Helvetica', sans-serif; background-color: #f3f4f6; padding: 20px; }
        .card { background-color: white; max-width: 500px; margin: 0 auto; padding: 30px; border-radius: 8px; border-top: 5px solid #10B981; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .header { text-align: center; border-bottom: 1px solid #eee; padding-bottom: 20px; margin-bottom: 20px; }
        .amount { font-size: 32px; font-weight: bold; color: #10B981; text-align: center; margin: 10px 0; }
        .details { background: #f9fafb; padding: 15px; border-radius: 6px; font-size: 14px; color: #555; margin-top: 20px; }
        .footer { text-align: center; font-size: 12px; color: #aaa; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h2 style="margin:0; color:#333;">¡Gracias por tu pago! 🌟</h2>
            <p style="margin:5px 0 0; color:#666;">{{ $gym->nombre }}</p>
        </div>

        <p>Hola <strong>{{ explode(' ', trim($member->name))[0] }}</strong>,</p>
        <p>Confirmamos que hemos recibido tu pago correctamente. Tu membresía ha sido renovada y puedes seguir entrenando sin interrupciones.</p>

        <div class="amount">
            ${{ number_format($payment->amount, 0, ',', '.') }}
        </div>

        <div class="details">
            <p style="margin:0;">📅 <strong>Fecha de pago:</strong> {{ \Carbon\Carbon::parse($payment->paid_at)->format('d/m/Y h:i A') }}</p>
            <p style="margin:5px 0 0;">💳 <strong>Método:</strong> {{ $payment->payment_method->name ?? 'Efectivo' }}</p>
        </div>

        <p style="text-align: center; margin-top: 25px; font-weight: bold; color: #374151;">
            ¡Nos vemos en el próximo entreno! 💪
        </p>
    </div>

    <div class="footer">
        Este es un comprobante automático de pago.
    </div>
</body>
</html>
