<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Support\Money;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OrdersByStatus extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return Filament::auth()->user()?->can('view_revenue') ?? false;
    }

    protected ?string $heading = 'Orders';

    protected function getStats(): array
    {
        /** @var array<string, int> $counts */
        $counts = Order::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $paidTotal = (int) Order::query()->where('payment_status', 'paid')->sum('total_kobo');
        $awaiting = (int) ($counts['pending_payment'] ?? 0);
        $open = (int) ($counts['new'] ?? 0) + (int) ($counts['preparing'] ?? 0);
        $shipped = (int) ($counts['shipped'] ?? 0) + (int) ($counts['delivered'] ?? 0);

        return [
            Stat::make('Revenue collected', Money::format($paidTotal))
                ->description('All paid orders')
                ->color('success'),
            Stat::make('Needs attention', (string) $open)
                ->description('New or being prepared')
                ->color($open > 0 ? 'warning' : 'gray'),
            Stat::make('Awaiting payment', (string) $awaiting)
                ->description('Checkout started, not paid')
                ->color('gray'),
            Stat::make('Shipped or delivered', (string) $shipped)
                ->color('info'),
        ];
    }
}
