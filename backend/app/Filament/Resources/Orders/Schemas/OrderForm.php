<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\Order;
use App\Support\Money;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;

class OrderForm
{
    /**
     * Staff can move an order through fulfilment and fix delivery details.
     * Line items, totals and payment state are not editable — an order's
     * contents are a record of what was bought and paid for.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Fulfilment')
                    ->columns(3)
                    ->schema([
                        TextInput::make('reference')
                            ->disabled()
                            ->dehydrated(false),
                        Select::make('status')
                            ->options(Order::STATUSES)
                            ->required()
                            ->native(false),
                        TextInput::make('payment_status')
                            ->label('Payment')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn (?string $state) => Order::PAYMENT_STATUSES[$state] ?? 'Not required'),
                    ]),

                Section::make('Customer')
                    ->columns(3)
                    ->schema([
                        TextInput::make('customer_name')->required(),
                        TextInput::make('customer_email')->email()->required(),
                        TextInput::make('customer_phone'),
                        Textarea::make('delivery_address')->rows(2)->columnSpanFull(),
                        Textarea::make('notes')->rows(2)->columnSpanFull(),
                    ]),

                Section::make('Items')
                    ->description('Read-only. Open the order to see the full breakdown.')
                    ->schema([
                        Text::make(fn (?Order $record): string => $record
                            ? $record->items
                                ->map(fn ($item) => "{$item->quantity} × {$item->product_name} — ".Money::format($item->line_total_kobo))
                                ->implode("\n")
                            : '—'),
                        Text::make(fn (?Order $record): string => $record
                            ? 'Total '.Money::format($record->total_kobo)
                            : '')
                            ->weight('bold'),
                    ]),
            ]);
    }
}
