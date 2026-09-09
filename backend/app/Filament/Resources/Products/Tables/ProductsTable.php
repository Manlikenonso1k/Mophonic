<?php

namespace App\Filament\Resources\Products\Tables;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use App\Support\Money;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                ImageColumn::make('image')
                    ->label('Photo')
                    ->disk('public')
                    ->height(52)
                    ->defaultImageUrl(fn (Product $record) => $record->image_url),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Product $record) => $record->unit_label),
                TextColumn::make('category.name')
                    ->badge()
                    ->sortable(),
                TextColumn::make('price_kobo')
                    ->label('Price')
                    ->formatStateUsing(fn (int $state) => Money::format($state))
                    ->sortable(),
                TextColumn::make('stock')
                    ->badge()
                    ->color(fn (int $state) => match (true) {
                        $state === 0 => 'danger',
                        $state < 6 => 'warning',
                        default => 'success',
                    })
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Live')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('is_active')->label('Visible in the shop'),
                TernaryFilter::make('stock')
                    ->label('Stock')
                    ->placeholder('Any')
                    ->trueLabel('In stock')
                    ->falseLabel('Sold out')
                    ->queries(
                        true: fn ($query) => $query->where('stock', '>', 0),
                        false: fn ($query) => $query->where('stock', '=', 0),
                        blank: fn ($query) => $query,
                    ),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (Product $record): bool => ProductResource::canEdit($record)),
                DeleteAction::make()
                    ->visible(fn (Product $record): bool => ProductResource::canDelete($record)),
            ])
            // No bulk delete for anyone who cannot delete: an empty
            // group would still draw the selection checkboxes.
            ->toolbarActions(ProductResource::canDeleteAny() ? [
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ] : []);
    }
}
