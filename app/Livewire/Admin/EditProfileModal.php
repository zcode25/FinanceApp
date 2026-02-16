<?php

namespace App\Livewire\Admin;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class EditProfileModal extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    #[On('open-admin-profile-modal')]
    public function open(): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $this->form->fill([
            'name' => $user?->name,
            'email' => $user?->email,
        ]);

        $this->dispatch('open-modal', id: 'admin-profile-modal');
    }

    public function mount(): void
    {
        // Safety check for logout or unauthenticated states
        if (!Auth::check()) {
            return;
        }
    }

    public function close(): void
    {
        $this->dispatch('close-modal', id: 'admin-profile-modal');
        $this->resetErrorBag();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique('users', 'email', ignorable: Auth::user()),
                TextInput::make('current_password')
                    ->password()
                    ->label('Current Password')
                    ->requiredWith('new_password')
                    ->rule(function () {
                        return function ($attribute, $value, $fail) {
                            /** @var \App\Models\User $user */
                            $user = Auth::user();
                            if ($user && $value && !Hash::check($value, $user->password)) {
                                $fail('The current password does not match our records.');
                            }
                        };
                    }),
                TextInput::make('new_password')
                    ->password()
                    ->label('New Password')
                    ->minLength(8)
                    ->confirmed()
                    ->dehydrated(fn($state) => filled($state)),
                TextInput::make('new_password_confirmation')
                    ->password()
                    ->label('Confirm New Password')
                    ->dehydrated(false),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $updateData = [
            'name' => $data['name'],
            'email' => $data['email'],
        ];

        if (!empty($data['new_password'])) {
            $updateData['password'] = Hash::make($data['new_password']);
        }

        $user->update($updateData);

        Notification::make()
            ->title('Profile updated successfully')
            ->success()
            ->send();

        $this->close();
    }

    public function render()
    {
        return view('livewire.admin.edit-profile-modal');
    }
}
