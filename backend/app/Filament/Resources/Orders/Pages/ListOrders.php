<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use Filament\Resources\Pages\ListRecords;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    /** Orders only ever arrive from checkout, so there is nothing to create. */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
