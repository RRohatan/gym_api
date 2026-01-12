<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .card { background: #f9f9f9; padding: 20px; border-radius: 8px; border-left: 5px solid #3b82f6; }
        .btn { background: #3b82f6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Hola, {{ $member->name }} 👋</h2>
        <p>Te escribimos de <strong>{{ $gymName }}</strong>.</p>

        <p>Nos encanta la energía que traes al gimnasio.
           Tu suscripción está por terminar,
            <strong>{{ \Carbon\Carbon::parse($fechaVencimiento)->format('d/m/Y') }}</strong> (en 3 días). pero tu transformación apenas comienza.</p>

        <p>¡Renueva y sigamos entrenando juntos!</p>

    </div>
</body>
</html>


