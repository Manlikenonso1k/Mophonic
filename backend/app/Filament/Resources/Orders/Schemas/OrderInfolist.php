<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\Order;
use App\Support\Money;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Order')
                    ->columns(4)
                    ->schema([
                        TextEntry::make('reference')
                            ->copyable()
                            ->weight('bold'),
                        TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn (?string $state) => Order::STATUSES[$state] ?? $state)
                            ->color(fn (?string $state) => match ($state) {
                                'new' => 'warning',
                                'preparing' => 'info',
                                'shipped' => 'info',
                                'delivered' => 'success',
                                'cancelled', 'payment_failed' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('payment_status')
                            ->label('Payment')
                            ->badge()
                            ->placeholder('Not required')
                            ->formatStateUsing(fn (?string $state) => Order::PAYMENT_STATUSES[$state] ?? $state)
                            ->color(fn (?string $state) => match ($state) {
                                'paid' => 'success',
                                'failed' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('created_at')
                            ->label('Placed')
                            ->dateTime('d M Y, H:i'),
                    ]),

                Section::make('Customer')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('customer_name'),
                        TextEntry::make('customer_email')->copyable(),
                        TextEntry::make('customer_phone')->placeholder('—'),
                        TextEntry::make('delivery_address')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('notes')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ]),

                Section::make('Items')
                    ->description('Recorded at checkout. Line items cannot be edited.')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->columns(4)
                            ->schema([
                                TextEntry::make('product_name')->label('Product'),
                                TextEntry::make('quantity')->label('Qty'),
                                TextEntry::make('unit_price_kobo')
                                    ->label('Unit price')
                                    ->formatStateUsing(fn (int $state) => Money::format($state)),
                                TextEntry::make('line_total_kobo')
                                    ->label('Line total')
                                    ->formatStateUsing(fn (int $state) => Money::format($state)),
                            ]),
                    ]),

                Section::make('Totals')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('subtotal_kobo')
                            ->label('Subtotal')
                            ->formatStateUsing(fn (int $state) => Money::format($state)),
                        TextEntry::make('delivery_fee_kobo')
                            ->label('Delivery')
                            ->formatStateUsing(fn (int $state) => Money::format($state)),
                        TextEntry::make('total_kobo')
                            ->label('Total')
                            ->weight('bold')
                            ->formatStateUsing(fn (int $state) => Money::format($state)),
                    ]),
            ]);
    }
}
