<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .titulo { color: #1f2937; }
        .alerta { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <h2 class="titulo">📊 Reporte Diario: {{ $gymName }}</h2>
    <p>Hola <strong>{{ $adminName }}</strong>, este es el resumen de hoy:</p>

    <h3>⚠️ Vencen en 3 días</h3>
    @if(count($proximos) > 0)
        <table>
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Vence</th>
                    <th>Plan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($proximos as $m)
                <tr>
                    <td>{{ $m->member->name }}</td>
                    <td>{{ \Carbon\Carbon::parse($m->end_date)->format('d/m/Y') }}</td>
                    <td>{{ $m->plan->name ?? 'N/A' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p><i>No hay vencimientos próximos hoy.</i></p>
    @endif

    <h3>⛔ Vencidos Hoy (Se generó deuda)</h3>
    @if(count($vencidos) > 0)
        <table>
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Deuda</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                @foreach($vencidos as $m)
                <tr>
                    <td>{{ $m->member->name }}</td>
                    <td class="alerta">${{ number_format($m->outstanding_balance) }}</td>
                    <td>Cobrar</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p><i>Nadie se venció hoy.</i></p>
    @endif
</body>
</html>
