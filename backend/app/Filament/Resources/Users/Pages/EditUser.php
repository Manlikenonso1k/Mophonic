<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\Auth;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (): bool => static::getResource()::canDelete($this->getRecord())),
        ];
    }

    /**
     * The form disables the role picker in these cases, but a disabled field
     * is a UI affordance, not a guarantee — the same rules are enforced here,
     * where the request actually lands.
     *
     * @throws Halt
     */
    protected function beforeSave(): void
    {
        /** @var User $record */
        $record = $this->getRecord();
        $submitted = collect($this->data['roles'] ?? [])->map(fn ($id) => (int) $id);
        $current = $record->roles->pluck('id')->map(fn ($id) => (int) $id);

        if ($submitted->sort()->values()->all() === $current->sort()->values()->all()) {
            return;
        }

        if ($record->is(Auth::user())) {
            $this->deny('You cannot change your own role.');
        }

        if ($record->isLastSuperAdmin()) {
            $this->deny('This is the last super admin. Promote someone else first.');
        }
    }

    /** @throws Halt */
    protected function deny(string $message): void
    {
        Notification::make()
            ->danger()
            ->title('Change refused')
            ->body($message)
            ->send();

        throw new Halt;
    }
}
