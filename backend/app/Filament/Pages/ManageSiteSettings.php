<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageSiteSettings extends Page
{
    use InteractsWithFormActions;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $title = 'Site settings';

    protected static ?string $navigationLabel = 'Site settings';

    protected static ?int $navigationSort = 3;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(SiteSetting::current()->attributesToArray());
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make($this->getFormActions())->key('form-actions'),
                ]),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Header')
                    ->columns(2)
                    ->schema([
                        TextInput::make('eyebrow')
                            ->label('Small line above the title')
                            ->required(),
                        TextInput::make('heading')
                            ->label('Page title')
                            ->required(),
                    ]),

                Section::make('Links')
                    ->columns(2)
                    ->schema([
                        TextInput::make('shop_url')->url()->required(),
                        TextInput::make('terms_url')->url()->required(),
                    ]),

                Section::make('Newsletter')
                    ->schema([
                        TextInput::make('newsletter_heading')->required(),
                    ]),

                Section::make('Menu')
                    ->description('The links inside the fullscreen menu overlay.')
                    ->schema([
                        Repeater::make('menu_items')
                            ->hiddenLabel()
                            ->columns(3)
                            ->reorderable()
                            ->defaultItems(0)
                            ->schema([
                                TextInput::make('label')->required(),
                                TextInput::make('url')->required(),
                                Toggle::make('external')
                                    ->label('Opens in a new tab')
                                    ->inline(false),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        SiteSetting::current()->update($this->form->getState());

        Notification::make()
            ->success()
            ->title('Settings saved')
            ->send();
    }

    /** @return array<Action> */
    public function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save changes')
                ->submit('save'),
        ];
    }
}
