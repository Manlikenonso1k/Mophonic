<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Orders\Pages\ViewOrderReceipt;
use App\Models\Order;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('reference')
                    ->searchable()
                    ->copyable()
                    ->weight('bold'),
                TextColumn::make('customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->description(fn (Order $record) => $record->customer_email),
                TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items')
                    ->badge(),
                TextColumn::make('total_kobo')
                    ->label('Total')
                    ->formatStateUsing(fn (int $state) => Money::format($state))
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => Order::STATUSES[$state] ?? $state)
                    ->color(fn (?string $state) => match ($state) {
                        'new' => 'warning',
                        'preparing', 'shipped' => 'info',
                        'delivered' => 'success',
                        'cancelled', 'payment_failed' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->placeholder('—')
                    ->formatStateUsing(fn (?string $state) => Order::PAYMENT_STATUSES[$state] ?? $state)
                    ->color(fn (?string $state) => match ($state) {
                        'paid' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->label('Placed')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(Order::STATUSES),
                SelectFilter::make('payment_status')
                    ->label('Payment')
                    ->options(Order::PAYMENT_STATUSES),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('receipt')
                    ->label('Receipt')
                    ->icon('heroicon-o-receipt-percent')
                    ->url(fn (Order $record): string => OrderResource::getUrl('receipt', ['record' => $record]))
                    ->visible(fn (): bool => ViewOrderReceipt::canAccessReceipt()),
                EditAction::make()
                    ->visible(fn (Order $record): bool => OrderResource::canEdit($record)),
            ])
            // No bulk delete for anyone who cannot delete: an empty
            // group would still draw the selection checkboxes.
            ->toolbarActions(OrderResource::canDeleteAny() ? [
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ] : []);
    }
}
