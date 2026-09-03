<?php

namespace App\Filament\Resources\Works\Tables;

use App\Models\Work;
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

class WorksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                ImageColumn::make('cover_image')
                    ->label('Cover')
                    ->disk('public')
                    ->height(56)
                    ->defaultImageUrl(fn (Work $record) => $record->cover_url),
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Work $record) => $record->slug),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => Work::TYPES[$state] ?? $state)
                    ->sortable(),
                IconColumn::make('has_video')
                    ->label('Video')
                    ->boolean()
                    ->getStateUsing(fn (Work $record) => filled($record->backgroundVideoSrc())),
                TextColumn::make('sort_order')
                    ->label('Order')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Live')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')->options(Work::TYPES),
                TernaryFilter::make('is_active')->label('Visible on the site'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
