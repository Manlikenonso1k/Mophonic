<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Account')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(120),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                    ]),

                Section::make('Password')
                    ->description('On an existing account, leave both fields empty to keep the current password.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->minLength(8)
                            ->same('password_confirmation')
                            ->required(fn (string $operation): bool => $operation === 'create')
                            // Hashed on the way in, and dropped entirely when
                            // left blank so an edit never blanks the hash.
                            ->dehydrateStateUsing(fn (?string $state): string => Hash::make($state))
                            ->dehydrated(fn (?string $state): bool => filled($state)),
                        TextInput::make('password_confirmation')
                            ->label('Confirm password')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(false),
                    ]),

                Section::make('Role')
                    ->schema([
                        Select::make('roles')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->required()
                            // Nobody edits their own role: an accidental
                            // self-demotion locks the panel's owner out.
                            ->disabled(fn (?User $record): bool => $record?->is(Auth::user()) ?? false)
                            ->helperText(fn (?User $record): ?string => $record?->is(Auth::user())
                                ? 'You cannot change your own role. Ask another super admin.'
                                : null),
                    ]),
            ]);
    }
}
