<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Models\Plan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\IconEntry;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'User Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Profile Information')
                    ->description('User personal details (Read-only for Admin).')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Placeholder::make('avatar_display')
                                    ->hiddenLabel()
                                    ->content(fn(User $record) => new \Illuminate\Support\HtmlString('
                                            <img src="' . ($record->avatar ? (str_starts_with($record->avatar, 'http') ? $record->avatar : (str_starts_with($record->avatar, 'avatars/') ? \Illuminate\Support\Facades\Storage::url($record->avatar) : \Illuminate\Support\Facades\Storage::url('avatars/' . $record->avatar))) : "https://ui-avatars.com/api/?name=" . urlencode($record->name) . "&color=6366f1&background=EEF2FF") . '" 
                                                 style="height: 80px; width:    
                                                 class="max-w-none object-cover object-center rounded-full ring-2 ring-white dark:ring-gray-900 shadow-sm">
                                        </div>
                                    ')),
                                Forms\Components\Placeholder::make('name')
                                    ->content(fn(User $record) => $record->name),
                                Forms\Components\Placeholder::make('email')
                                    ->content(fn(User $record) => new \Illuminate\Support\HtmlString('
                                        <div class="fi-ta-text-item inline-flex items-center gap-1.5 ' . ($record->email_verified_at ? 'fi-color-success' : 'fi-color-gray') . '" style="' . ($record->email_verified_at ? '--c-500:var(--success-500);' : '') . '">
                                            ' . ($record->email_verified_at ? '<svg class="fi-ta-text-item-icon h-5 w-5 text-custom-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon"><path fill-rule="evenodd" d="M16.403 12.652a3 3 0 0 0 0-5.304 3 3 0 0 0-3.75-3.751 3 3 0 0 0-5.305 0 3 3 0 0 0-3.751 3.75 3 3 0 0 0 0 5.305 3 3 0 0 0 3.75 3.751 3 3 0 0 0 5.305 0 3 3 0 0 0 3.751-3.75Zm-2.546-4.46a.75.75 0 0 0-1.214-.883l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd"></path></svg>' : '') . '
                                            <span class="fi-ta-text-item-label text-sm leading-6 text-gray-500 dark:text-gray-400">
                                                ' . $record->email . '
                                            </span>
                                        </div>
                                    ')),
                            ]),
                    ]),

                Forms\Components\Section::make('Account & Status')
                    ->description('Manage user access and subscription settings.')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Account Status')
                                    ->helperText('Enable or disable user access.')
                                    ->default(true)
                                    ->onColor('success')
                                    ->offColor('danger'),
                                Forms\Components\Toggle::make('is_premium')
                                    ->label('Premium Access')
                                    ->helperText('Manually toggle premium status.')
                                    ->onColor('warning'),
                                Forms\Components\DateTimePicker::make('subscription_until')
                                    ->label('Subscription Expiry')
                                    ->helperText('Date when premium access expires.'),
                            ]),
                    ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistSection::make('User Profile')
                    ->compact()
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                ImageEntry::make('avatar')
                                    ->circular()
                                    ->height(80)
                                    ->hiddenLabel()
                                    ->state(fn (User $record) => $record->avatar ? (str_starts_with($record->avatar, 'http') ? $record->avatar : (str_starts_with($record->avatar, 'avatars/') ? $record->avatar : 'avatars/' . $record->avatar)) : null)
                                    ->defaultImageUrl(fn(User $record) => "https://ui-avatars.com/api/?name=" . urlencode($record->name) . "&color=6366f1&background=EEF2FF"),
                                TextEntry::make('name')
                                    ->weight('bold')
                                    ->size('lg'),
                                TextEntry::make('email')
                                    ->icon(fn(User $record) => $record->email_verified_at ? 'heroicon-m-check-badge' : 'heroicon-m-envelope')
                                    ->iconColor(fn(User $record) => $record->email_verified_at ? 'success' : 'gray')
                                    ->copyable(),
                            ]),
                    ]),

                InfolistSection::make('Account Status')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('latestTransaction.plan.name')
                                    ->label('Current Plan')
                                    ->badge()
                                    ->state(function (User $record) {
                                        if (!$record->is_premium) {
                                            return static::getPlanName(1);
                                        }

                                        return $record->latestTransaction?->plan?->name
                                            ?? ($record->subscription_until === null ? static::getPlanName(4) : 'Premium');
                                    })
                                    ->color(function (User $record) {
                                        if (!$record->is_premium)
                                            return 'gray';

                                        $plan = $record->latestTransaction?->plan;
                                        if (!$plan && $record->subscription_until === null) {
                                            $plan = Plan::where('id', 4)->first();
                                        }

                                        return match ($plan?->duration_type) {
                                            'month' => 'info',
                                            'year' => 'success',
                                            'lifetime' => 'purple',
                                            default => 'info',
                                        };
                                    }),
                                TextEntry::make('subscription_until')
                                    ->dateTime('M d, Y')
                                    ->label('Expires On')
                                    ->placeholder('N/A (Free Tier)'),
                                TextEntry::make('last_login_at')
                                    ->dateTime('M d, Y H:i')
                                    ->label('Last Activity')
                                    ->placeholder('Never logged in'),
                                IconEntry::make('is_active')
                                    ->label('Status')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('danger'),
                            ]),
                    ]),

                InfolistSection::make('Service Usage')
                    ->schema([
                        Grid::make(5)
                            ->schema([
                                TextEntry::make('transactions_usage')
                                    ->label('Transaction')
                                    ->weight('bold')
                                    ->state(
                                        fn(User $record) =>
                                        $record->financialTransactions()->count() . ' / Unlimited'
                                    ),

                                TextEntry::make('wallets_usage')
                                    ->label('Walet')
                                    ->weight('bold')
                                    ->state(
                                        fn(User $record) =>
                                        $record->wallets()->count() . ' / ' . ($record->is_premium ? 'Unlimited' : '3')
                                    ),

                                TextEntry::make('budgets_usage')
                                    ->label('Budget')
                                    ->weight('bold')
                                    ->state(
                                        fn(User $record) =>
                                        $record->budgets()->count() . ' / ' . ($record->is_premium ? 'Unlimited' : '1')
                                    ),

                                TextEntry::make('goals_usage')
                                    ->label('Goals')
                                    ->weight('bold')
                                    ->state(
                                        fn(User $record) =>
                                        $record->goals()->count() . ' / ' . ($record->is_premium ? 'Unlimited' : '1')
                                    ),

                                TextEntry::make('categories_usage')
                                    ->label('Category custom')
                                    ->weight('bold')
                                    ->state(
                                        fn(User $record) =>
                                        $record->customCategories()->count() . ' / ' . ($record->is_premium ? 'Unlimited' : '3')
                                    ),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->circular()
                    ->state(fn (User $record) => $record->avatar ? (str_starts_with($record->avatar, 'http') ? $record->avatar : (str_starts_with($record->avatar, 'avatars/') ? $record->avatar : 'avatars/' . $record->avatar)) : null)
                    ->defaultImageUrl(fn(User $record) => "https://ui-avatars.com/api/?name=" . urlencode($record->name) . "&color=6366f1&background=EEF2FF"),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->icon(fn(User $record) => $record->email_verified_at ? 'heroicon-m-check-badge' : 'heroicon-m-envelope')
                    ->iconColor(fn(User $record) => $record->email_verified_at ? 'success' : 'gray')
                    ->color('gray'),
                Tables\Columns\TextColumn::make('latestTransaction.plan.name')
                    ->label('Package')
                    ->badge()
                    ->state(function (User $record) {
                        if (!$record->is_premium) {
                            return static::getPlanName(1);
                        }

                        return $record->latestTransaction?->plan?->name
                            ?? ($record->subscription_until === null ? static::getPlanName(4) : 'Premium');
                    })
                    ->color(function (User $record) {
                        if (!$record->is_premium)
                            return 'gray';

                        $plan = $record->latestTransaction?->plan;
                        if (!$plan && $record->subscription_until === null) {
                            $plan = Plan::where('id', 4)->first();
                        }

                        return match ($plan?->duration_type) {
                            'month' => 'info',
                            'year' => 'success',
                            'lifetime' => 'purple',
                            default => 'info',
                        };
                    }),
                Tables\Columns\TextColumn::make('subscription_until')
                    ->dateTime('M d, Y')
                    ->label('Expires')
                    ->placeholder('Never'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M d, Y')
                    ->label('Joined')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_premium'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                ])
                    ->color('gray')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->tooltip('Actions'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Delete bulk action removed
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['latestTransaction.plan'])
            ->where('is_admin', false);
    }

    protected static ?array $cachedPlans = null;

    protected static function getPlanName(int $id): string
    {
        if (static::$cachedPlans === null) {
            static::$cachedPlans = Plan::all()->pluck('name', 'id')->toArray();
        }

        return static::$cachedPlans[$id] ?? ($id === 1 ? 'Starter' : ($id === 4 ? 'Lifetime' : 'Unknown'));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
        ];
    }
}
