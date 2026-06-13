<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identity & Authentication')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Username')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        TextInput::make('forename')
                            ->maxLength(255),

                        TextInput::make('lastname')
                            ->maxLength(255),

                        DatePicker::make('birthday')
                            ->native(false)
                            ->maxDate(now()),

                        Select::make('gender')
                            ->options([
                                'male' => 'Male',
                                'female' => 'Female',
                            ])
                            ->native(false),

                        Select::make('role')
                            ->options([
                                'admin' => 'Administrator',
                                'student' => 'Student',
                            ])
                            ->required()
                            ->default('student')
                            ->native(false),

                        TextInput::make('password')
                            ->password()
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->maxLength(255)
                            ->placeholder(fn (string $context): string => $context === 'edit' ? 'Leave blank to keep current password' : ''),
                    ]),

                Section::make('Progression Metrics')
                    ->description('Direct adjustments to player level parameters')
                    ->columns(3)
                    ->visible(fn (Form $form, ?User $record) => $record === null || $record->role === 'student')
                    ->schema([
                        TextInput::make('level')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required(),

                        TextInput::make('xp')
                            ->label('XP Balance')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->required(),

                        TextInput::make('coins')
                            ->label('Coin Balance')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->required(),
                    ]),
            ]);
    }
}
