<?php
namespace App\Filament\Resources\InstructorPaymentResource\Widgets;

use App\Models\InstructorPayment;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class PaymentStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Liquidación';
    protected static ?int $sort = 2;

    // Propiedad para controlar el tamaño del widget
    protected int|string|array $columnSpan = 1;

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getData(): array
    {
        // Contar la cantidad de pagos por cada estado
        $statusCounts = InstructorPayment::select('payment_status', DB::raw('count(*) as count'))
                                        ->groupBy('payment_status')
                                        ->pluck('count', 'payment_status')
                                        ->toArray();

        $paidCount = $statusCounts['paid'] ?? 0;
        $pendingCount = $statusCounts['pending'] ?? 0;

        return [
            'datasets' => [
                [
                    'label' => 'Estado de Pago',
                    'data' => [$paidCount, $pendingCount],
                    'backgroundColor' => [
                        '#10B981', // Color verde para 'paid' (success)
                        '#F59E0B', // Color naranja para 'pending' (warning)
                    ],
                    'hoverOffset' => 4,
                ],
            ],
            'labels' => ['Pagados', 'Pendientes'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
            'aspectRatio' => 1.5, // Hace el gráfico más compacto
        ];
    }

    // Método para controlar el span de columnas
    public function getColumnSpan(): int|string|array
    {
        return $this->columnSpan;
    }

    // Método alternativo para definir columnas por defecto
    public static function getColumns(): int|string|array
    {
        return 1; // Tamaño mínimo - 1 columna
    }
}
