<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Support\Money;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Product')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $operation, $state, callable $set) => $operation === 'create'
                                ? $set('slug', Str::slug((string) $state))
                                : null),
                        TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->helperText('Product page address: /shop/{slug}'),
                        Select::make('category_id')
                            ->label('Category')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('unit_label')
                            ->label('Unit')
                            ->placeholder('500g bag')
                            ->helperText('Shown next to the price.'),
                        Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('Photo')
                    ->description('Upload an image, or paste a URL if it is hosted elsewhere.')
                    ->columns(2)
                    ->schema([
                        FileUpload::make('image')
                            ->label('Upload photo')
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('products')
                            ->visibility('public')
                            ->maxSize(20480)
                            ->helperText('An uploaded file always wins over the URL below.'),
                        TextInput::make('image_url')
                            ->label('…or photo URL')
                            ->url()
                            ->maxLength(2048),
                    ]),

                Section::make('Price & stock')
                    ->columns(3)
                    ->schema([
                        // Stored in kobo; edited in naira.
                        TextInput::make('price_kobo')
                            ->label('Price')
                            ->prefix('₦')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->formatStateUsing(fn (?int $state) => $state === null ? null : Money::toNaira($state))
                            ->dehydrateStateUsing(fn ($state) => Money::toKobo((float) $state)),
                        TextInput::make('stock')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required()
                            ->helperText('At zero the product shows as sold out.'),
                        TextInput::make('sort_order')
                            ->label('Order')
                            ->numeric()
                            ->default(0)
                            ->required(),
                        Toggle::make('is_active')
                            ->label('Visible in the shop')
                            ->default(true),
                    ]),
            ]);
    }
}
