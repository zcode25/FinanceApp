<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('sync_expired')
                ->label('Sync Expired')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Sync Expired Subscriptions')
                ->modalDescription('This will downgrade all premium users whose subscription period has ended.')
                ->modalSubmitActionLabel('Sync Now')
                ->action(function () {
                    $count = \App\Models\User::where('is_premium', true)
                        ->whereNotNull('subscription_until')
                        ->where('subscription_until', '<', now())
                        ->update(['is_premium' => false]);

                    \Filament\Notifications\Notification::make()
                        ->title('Sync Complete')
                        ->body($count . ' user(s) have been downgraded to Starter.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
