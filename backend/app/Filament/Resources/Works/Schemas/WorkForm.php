<?php

namespace App\Filament\Resources\Works\Schemas;

use App\Models\Work;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class WorkForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Details')
                    ->disabled(fn (): bool => ! self::canEditDetails())
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $operation, $state, callable $set) => $operation === 'create'
                                ? $set('slug', Str::slug($state))
                                : null)
                            ->helperText('Shown as the caption under the carousel.'),
                        TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->helperText('Used in the "Explore" link: /mophonik/{slug}'),
                        Select::make('type')
                            ->options(Work::TYPES)
                            ->default('video')
                            ->required(),
                        TextInput::make('link_url')
                            ->label('Custom link (optional)')
                            ->url()
                            ->helperText('Overrides the slug link. Use for external destinations.'),
                        Textarea::make('description')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make('Cover artwork')
                    ->disabled(fn (): bool => ! self::canEditImages())
                    ->description('The vertical poster shown in the carousel. Upload a file, or paste a URL if the artwork is hosted elsewhere.')
                    ->columns(2)
                    ->schema([
                        FileUpload::make('cover_image')
                            ->label('Upload cover')
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('works/covers')
                            ->visibility('public')
                            ->maxSize(20480)
                            ->helperText('An uploaded file always wins over the URL below.'),
                        TextInput::make('cover_url')
                            ->label('…or cover URL')
                            ->url()
                            ->maxLength(2048),
                    ]),

                Section::make('Background video')
                    ->disabled(fn (): bool => ! self::canEditImages())
                    ->description('Full-screen looping video that plays behind this slide.')
                    ->columns(2)
                    ->schema([
                        FileUpload::make('background_video')
                            ->label('Upload video')
                            ->disk('public')
                            ->directory('works/videos')
                            ->visibility('public')
                            ->acceptedFileTypes(['video/mp4', 'video/webm', 'video/quicktime'])
                            ->maxSize(204800)
                            ->helperText('MP4 recommended. Max 200 MB.'),
                        TextInput::make('background_video_url')
                            ->label('…or video URL')
                            ->url()
                            ->maxLength(2048),
                    ]),

                Section::make('Placement')
                    ->disabled(fn (): bool => ! self::canEditDetails())
                    ->columns(2)
                    ->schema([
                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->helperText('Lower numbers appear first. You can also drag rows on the list screen.'),
                        Toggle::make('is_active')
                            ->label('Visible on the site')
                            ->default(true),
                    ]),
            ]);
    }

    /** Disabled fields are never dehydrated, so this holds server-side too. */
    protected static function canEditDetails(): bool
    {
        return Filament::auth()->user()?->can('update_work') ?? false;
    }

    protected static function canEditImages(): bool
    {
        $user = Filament::auth()->user();

        return ($user?->can('update_work') || $user?->can('update_work_image')) ?? false;
    }
}
