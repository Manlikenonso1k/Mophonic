<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Support\Money;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class SalesOverview extends ChartWidget
{
    public static function canView(): bool
    {
        return Filament::auth()->user()?->can('view_revenue') ?? false;
    }

    protected ?string $heading = 'Revenue';

    protected ?string $description = 'Paid orders, by day.';

    protected int|string|array $columnSpan = 'full';

    public ?string $filter = '30';

    /** @return array<string, string> */
    protected function getFilters(): ?array
    {
        return [
            '7' => 'Last 7 days',
            '30' => 'Last 30 days',
            '90' => 'Last 90 days',
        ];
    }

    /** @return array<string, mixed> */
    protected function getData(): array
    {
        $days = (int) ($this->filter ?? 30);
        $start = Carbon::today()->subDays($days - 1);

        // One grouped query, then filled out so quiet days still plot a zero.
        $paid = Order::query()
            ->where('payment_status', 'paid')
            ->where('paid_at', '>=', $start)
            ->get(['paid_at', 'total_kobo'])
            ->groupBy(fn (Order $order) => $order->paid_at?->toDateString())
            ->map(fn ($orders) => (int) $orders->sum('total_kobo'));

        $labels = [];
        $values = [];

        for ($offset = 0; $offset < $days; $offset++) {
            $date = $start->copy()->addDays($offset);
            $labels[] = $date->format($days > 31 ? 'j M' : 'D j');
            $values[] = Money::toNaira($paid->get($date->toDateString(), 0));
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue (₦)',
                    'data' => $values,
                    'borderColor' => '#e2924a',
                    'backgroundColor' => 'rgba(226, 146, 74, 0.16)',
                    'fill' => true,
                    'tension' => 0.35,
                    'pointRadius' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
