<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Services\ReceiptRenderer;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Symfony\Component\HttpFoundation\Response;

/**
 * The staff copy: everything on the customer receipt plus the internal fields.
 * Read-only for everyone — a receipt is a record of what happened, and a waiter
 * has no edit, refund or delete path from here.
 */
class ViewOrderReceipt extends Page
{
    use InteractsWithRecord;

    protected static string $resource = OrderResource::class;

    protected string $view = 'filament.orders.receipt';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        abort_unless(static::canAccessReceipt(), 403);
    }

    public static function canAccessReceipt(): bool
    {
        return Filament::auth()->user()?->can('view_order_receipt') ?? false;
    }

    public function getTitle(): string|Htmlable
    {
        return "Receipt {$this->getRecord()->reference}";
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label('Print')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->action(null)
                ->extraAttributes(['onclick' => 'window.print()']),

            Action::make('download')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn (): Response => app(ReceiptRenderer::class)->download($this->getRecord(), staff: true)),
        ];
    }

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        /** @var Order $order */
        $order = $this->getRecord();

        return [
            'receipt' => app(ReceiptRenderer::class)->view($order, staff: true)->render(),
        ];
    }
}
