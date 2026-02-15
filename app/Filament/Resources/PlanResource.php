<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlanResource\Pages;
use App\Filament\Resources\PlanResource\RelationManagers;
use App\Models\Plan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'System Settings';

    protected static ?string $slug = 'pricing-management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(1)
                    ->schema([
                        Forms\Components\Section::make('Package Information')
                            ->description('General configuration for this pricing package.')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Package Name')
                                            ->readOnly()
                                            ->required(),
                                        Forms\Components\TextInput::make('price')
                                            ->label('Current Price')
                                            ->required()
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->helperText('This price will be reflected across the website and checkout flow.'),
                                    ])
                            ]),

                        Forms\Components\Section::make('Subscription Logic')
                            ->description('Define how long this package remains active for the user.')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\Select::make('duration_type')
                                            ->label('Duration Type')
                                            ->options([
                                                'month' => 'Monthly',
                                                'year' => 'Yearly',
                                                'lifetime' => 'Lifetime (No Expiry)',
                                            ])
                                            ->required()
                                            ->live(),
                                        Forms\Components\TextInput::make('duration_value')
                                            ->label('Duration Value')
                                            ->numeric()
                                            ->placeholder('Unlimited')
                                            ->helperText('Number of months/years. Leave empty for Unlimited Access.')
                                            ->required(fn(Forms\Get $get) => $get('duration_type') !== 'lifetime')
                                            ->hidden(fn(Forms\Get $get) => $get('duration_type') === 'lifetime'),
                                    ])
                            ]),
                    ])->columns(1)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Package')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('price')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'month' => 'info',
                        'year' => 'success',
                        'lifetime' => 'purple',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => ucfirst($state)),
                Tables\Columns\TextColumn::make('duration_value')
                    ->label('Duration')
                    ->state(function ($record) {
                        if ($record->duration_type === 'lifetime') {
                            return 'Unlimited';
                        }

                        $value = $record->duration_value;
                        $type = $record->duration_type;

                        if (!$value && $type !== 'lifetime') {
                            return 'Unlimited';
                        }

                        $unit = $type === 'month' ? 'Month' : 'Year';
                        if ($value > 1) {
                            $unit .= 's';
                        }

                        return "{$value} {$unit}";
                    }),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('M d, Y H:i')
                    ->label('Last Updated'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                // Disable bulk actions
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePlans::route('/'),
        ];
    }
}
