<?php

namespace App\Filament\Resources\Categories\RelationManagers;

use App\Filament\Resources\Products\Schemas\ProductForm;
use App\Models\Product;
use App\Support\Money;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    protected static ?string $title = 'Products in this category';

    /** Same form as the Products screen, so the two never drift apart. */
    public function form(Schema $schema): Schema
    {
        return ProductForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                ImageColumn::make('image')
                    ->label('Photo')
                    ->disk('public')
                    ->height(44)
                    ->defaultImageUrl(fn (Product $record) => $record->image_url),
                TextColumn::make('name')->searchable(),
                TextColumn::make('price_kobo')
                    ->label('Price')
                    ->formatStateUsing(fn (int $state) => Money::format($state)),
                TextColumn::make('stock')->badge(),
                IconColumn::make('is_active')->label('Live')->boolean(),
            ])
            ->headerActions([
                CreateAction::make(),
                AssociateAction::make()
                    ->label('Move a product here')
                    ->recordSelectSearchColumns(['name']),
            ])
            ->recordActions([
                EditAction::make(),
                DissociateAction::make()->label('Remove from category'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make(),
                ]),
            ]);
    }
}
