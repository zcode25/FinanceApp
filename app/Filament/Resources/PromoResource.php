<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PromoResource\Pages;
use App\Filament\Resources\PromoResource\RelationManagers;
use App\Models\Promo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PromoResource extends Resource
{
    protected static ?string $model = Promo::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Promo Codes';

    protected static ?string $pluralLabel = 'Promo Codes';

    protected static ?string $modelLabel = 'Promo Code';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Promo Details')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->placeholder('e.g. SAVE50')
                            ->required()
                            ->unique(ignorable: fn($record) => $record)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('discount_percentage')
                            ->numeric()
                            ->label('Discount Percentage')
                            ->suffix('%')
                            ->minValue(1)
                            ->maxValue(100)
                            ->required(),
                        Forms\Components\DateTimePicker::make('valid_until')
                            ->label('Expiration Date')
                            ->native(false),
                        Forms\Components\TextInput::make('usage_limit')
                            ->numeric()
                            ->label('Max Usage')
                            ->default(100)
                            ->minValue(1),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Is Active')
                            ->default(true)
                            ->inline(false),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->weight('bold')
                    ->copyable()
                    ->icon('heroicon-m-ticket'),
                Tables\Columns\TextColumn::make('discount_percentage')
                    ->label('Discount')
                    ->suffix('%')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('usage_limit')
                    ->label('Usage')
                    ->formatStateUsing(fn(Promo $record) => "{$record->used_count} / " . ($record->usage_limit ?: '∞')),
                Tables\Columns\TextColumn::make('valid_until')
                    ->dateTime('M d, Y')
                    ->label('Expires')
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPromos::route('/'),
        ];
    }
}
