<?php

namespace App\Filament\Resources\StoreItems\Schemas;

use App\Enums\PurchaseType;
use App\Enums\StoreItemType;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class StoreItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Item Details')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('icon')
                        ->label('Icon (Emoji)')
                        ->maxLength(10)
                        ->placeholder('e.g. 🏆'),

                    FileUpload::make('image')
                        ->label('Item Image')
                        ->image()
                        ->disk('public')
                        ->directory('store-items')
                        ->helperText(fn (Get $get): ?string => self::typeValue($get) === 'avatar'
                            ? 'Upload a square image — this will be shown as the student\'s avatar in the nav, leaderboard, and profile.'
                            : null)
                        ->columnSpanFull(),

                    Textarea::make('description')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),

            Section::make('Pricing & Availability')
                ->columns(2)
                ->schema([
                    Select::make('type')
                        ->options(StoreItemType::class)
                        ->required()
                        ->native(false)
                        ->live(),

                    Select::make('purchase_type')
                        ->options(PurchaseType::class)
                        ->required()
                        ->native(false)
                        ->live(),

                    TextInput::make('price_coins')
                        ->label('Price (Coins)')
                        ->numeric()
                        ->minValue(0)
                        ->required(),

                    TextInput::make('stock_limit')
                        ->label('Stock Limit')
                        ->numeric()
                        ->minValue(1)
                        ->helperText('Total number of students who can purchase this item.')
                        ->visible(fn (Get $get): bool => self::purchaseTypeValue($get) === PurchaseType::OneTime->value),

                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->columnSpanFull(),
                ]),

            Section::make('Consumable Effect')
                ->schema([
                    KeyValue::make('effect_config')
                        ->label('Effect Configuration')
                        ->helperText('streak_freeze → {"quantity": 1}  |  xp_boost → {"multiplier": 2, "lessons": 3}')
                        ->addActionLabel('Add key')
                        ->reorderable(false),
                ])
                ->visible(fn (Get $get): bool => StoreItemType::tryFrom((string) self::typeValue($get))?->isConsumable() ?? false),

            Section::make('Title Style')
                ->schema([
                    ColorPicker::make('display_config.color')
                        ->label('Title Color')
                        ->helperText("Color shown for the title text under the student's name.")
                        ->columnSpanFull(),
                ])
                ->visible(fn (Get $get): bool => self::typeValue($get) === StoreItemType::Title->value),
        ]);
    }

    private static function typeValue(Get $get): ?string
    {
        $type = $get('type');

        return $type instanceof StoreItemType ? $type->value : $type;
    }

    private static function purchaseTypeValue(Get $get): ?string
    {
        $purchaseType = $get('purchase_type');

        return $purchaseType instanceof PurchaseType ? $purchaseType->value : $purchaseType;
    }
}
