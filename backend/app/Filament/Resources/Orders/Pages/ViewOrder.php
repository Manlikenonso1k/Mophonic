<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('receipt')
                ->label('Receipt')
                ->icon('heroicon-o-receipt-percent')
                ->color('gray')
                ->url(fn (): string => OrderResource::getUrl('receipt', ['record' => $this->getRecord()]))
                ->visible(fn (): bool => ViewOrderReceipt::canAccessReceipt()),
            EditAction::make()
                ->visible(fn (): bool => OrderResource::canEdit($this->getRecord())),
        ];
    }
}
