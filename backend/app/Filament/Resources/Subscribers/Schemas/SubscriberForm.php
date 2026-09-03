<?php

namespace App\Filament\Resources\Subscribers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SubscriberForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                TextInput::make('source')
                    ->required()
                    ->default('mophonik'),
            ]);
    }
}
