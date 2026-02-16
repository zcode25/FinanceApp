<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionTransactionResource\Pages;
use App\Filament\Resources\SubscriptionTransactionResource\RelationManagers;
use App\Models\SubscriptionTransaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Grid;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SubscriptionTransactionResource extends Resource
{
    protected static ?string $model = SubscriptionTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Transactions';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Transaction Details')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('external_id')
                            ->default(fn() => 'ORD-' . strtoupper(uniqid()))
                            ->readOnly()
                            ->required()
                            ->label('Order ID'),
                        Forms\Components\Select::make('plan_id')
                            ->options([
                                'monthly' => 'Professional (Monthly)',
                                'yearly' => 'Master (Yearly)',
                                'lifetime' => 'Lifetime Access',
                            ])
                            ->required()
                            ->label('Subscription Plan'),
                        Forms\Components\TextInput::make('amount')
                            ->numeric()
                            ->prefix('Rp')
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'settlement' => 'Settlement (Success)',
                                'capture' => 'Capture (Success)',
                                'deny' => 'Deny',
                                'cancel' => 'Cancel',
                                'expire' => 'Expire',
                                'failure' => 'Failure',
                            ])
                            ->required()
                            ->default('pending')
                            ->native(false),
                    ])->columns(2),

                Forms\Components\Section::make('Technical Data')
                    ->schema([
                        Forms\Components\TextInput::make('snap_token')
                            ->label('Midtrans Snap Token')
                            ->helperText('Internal token used for payment processing.')
                            ->readOnly()
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Transaction Summary')
                    ->description('Detailed information about this subscription payment.')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('status')
                                    ->badge()
                                    ->color(fn(string $state): string => match ($state) {
                                        'success' => 'success',
                                        'pending' => 'warning',
                                        'failed' => 'danger',
                                        default => 'gray',
                                    }),
                                TextEntry::make('created_at')
                                    ->dateTime('M d, Y H:i')
                                    ->label('Transaction Date'),
                                TextEntry::make('external_id')
                                    ->weight('bold')
                                    ->label('Order ID'),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextEntry::make('user.name')
                                    ->weight('bold')
                                    ->label('Customer Name'),
                                TextEntry::make('user.email')
                                    ->label('Customer Email'),
                                TextEntry::make('plan.name')
                                    ->label('Plan')
                                    ->badge()
                                    ->color(fn(SubscriptionTransaction $record): string => match ($record->plan?->duration_type) {
                                        'month' => 'info',
                                        'year' => 'success',
                                        'lifetime' => 'purple',
                                        default => 'gray',
                                    }),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextEntry::make('amount')
                                    ->money('IDR', locale: 'id')
                                    ->weight('black')
                                    ->size('lg')
                                    ->color('success')
                                    ->label('Amount Paid'),
                                TextEntry::make('payment_type')
                                    ->formatStateUsing(fn(string $state) => ucfirst(str_replace('_', ' ', $state)))
                                    ->label('Payment Method'),
                                TextEntry::make('promo_code_id')
                                    ->placeholder('None')
                                    ->label('Promo Code Used'),
                            ]),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'success' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn(string $state): string => match ($state) {
                        'success' => 'heroicon-m-check-circle',
                        'pending' => 'heroicon-m-clock',
                        'failed' => 'heroicon-m-x-circle',
                        default => 'heroicon-m-question-mark-circle',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->label('Date'),
                Tables\Columns\TextColumn::make('external_id')
                    ->searchable()
                    ->copyable()
                    ->label('Order ID'),
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()
                    ->weight('bold')
                    ->label('Customer'),
                Tables\Columns\TextColumn::make('plan.name')
                    ->label('Plan')
                    ->badge()
                    ->color(fn(SubscriptionTransaction $record): string => match ($record->plan?->duration_type) {
                        'month' => 'info',
                        'year' => 'success',
                        'lifetime' => 'purple',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('amount')
                    ->money('IDR', locale: 'id')
                    ->weight('bold')
                    ->color('success')
                    ->alignRight(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'success' => 'Success',
                        'failed' => 'Failed',
                    ]),
                Tables\Filters\SelectFilter::make('plan_id')
                    ->options([
                        'monthly' => 'Professional (Monthly)',
                        'yearly' => 'Master (Yearly)',
                        'lifetime' => 'Lifetime Access',
                    ])
                    ->label('Plan'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\Action::make('check_status')
                        ->label('Check Status')
                        ->icon('heroicon-m-arrow-path')
                        ->color('info')
                        ->action(function (SubscriptionTransaction $record) {
                            try {
                                \Midtrans\Config::$serverKey = env('MIDTRANS_SERVER_KEY');
                                \Midtrans\Config::$isProduction = env('MIDTRANS_IS_PRODUCTION', false);
                                \Midtrans\Config::$isSanitized = true;
                                \Midtrans\Config::$is3ds = true;

                                $status = (object) \Midtrans\Transaction::status($record->external_id);
                                $transactionStatus = $status->transaction_status;
                                $fraudStatus = $status->fraud_status;
                                $paymentType = $status->payment_type;

                                if ($transactionStatus == 'capture') {
                                    if ($fraudStatus == 'challenge') {
                                        $record->update([
                                            'status' => 'pending',
                                            'payment_type' => $paymentType
                                        ]);
                                    } else if ($fraudStatus == 'accept') {
                                        $record->markAsSuccess($paymentType);
                                    }
                                } else if ($transactionStatus == 'settlement') {
                                    $record->markAsSuccess($paymentType);
                                } else if ($transactionStatus == 'pending') {
                                    $record->update([
                                        'status' => 'pending',
                                        'payment_type' => $paymentType
                                    ]);
                                } else if ($transactionStatus == 'deny' || $transactionStatus == 'expire' || $transactionStatus == 'cancel' || $transactionStatus == 'failure') {
                                    $record->update([
                                        'status' => 'failed',
                                        'payment_type' => $paymentType
                                    ]);
                                }

                                \Filament\Notifications\Notification::make()
                                    ->title('Status Updated')
                                    ->body("Transaction is now: {$record->status}")
                                    ->success()
                                    ->send();

                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Error Checking Status')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    Tables\Actions\EditAction::make()
                        ->label('Override Status')
                        ->icon('heroicon-m-pencil-square')
                        ->color('warning')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->options([
                                    'pending' => 'Pending',
                                    'success' => 'Success',
                                    'failed' => 'Failed',
                                ])
                                ->required()
                                ->selectablePlaceholder(false)
                                // Disable if status is already success or failed (Final State)
                                ->disabled(fn(SubscriptionTransaction $record) => in_array($record->status, ['success', 'failed']))
                                ->helperText(fn(SubscriptionTransaction $record) => in_array($record->status, ['success', 'failed'])
                                    ? 'Status is final and cannot be changed.'
                                    : 'You can manually update Pending transactions to Success or Failed.'),
                            Forms\Components\Hidden::make('payment_type')
                                ->default('manual_override'),
                        ])
                        ->modalHeading('Manual Status Override')
                        ->modalDescription('Use this only if Midtrans fails or for manual payments. Changing to Success will activate the subscription.')
                        ->after(function (SubscriptionTransaction $record) {
                            // Trigger Side Effects (Activate Subscription) if status changed to Success
                            if ($record->status === 'success') {
                                $record->markAsSuccess('manual_override');

                                \Filament\Notifications\Notification::make()
                                    ->title('Subscription Activated Manually')
                                    ->success()
                                    ->send();
                            }
                        })
                        ->visible(fn(SubscriptionTransaction $record) => $record->status === 'pending'), // Only show for Pending transactions
                ])
                    ->color('gray') // Neutral Color for Trigger
                    ->icon('heroicon-m-ellipsis-vertical') // Three Dots Icon
                    ->tooltip('Actions'),
            ])
            ->bulkActions([
                // No Bulk Actions allowed for Audit Log integrity
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'plan']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptionTransactions::route('/'),
            // Create and Edit pages removed for security
        ];
    }
}
