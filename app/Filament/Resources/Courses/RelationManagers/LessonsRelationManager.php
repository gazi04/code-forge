<?php

namespace App\Filament\Resources\Courses\RelationManagers;

use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class LessonsRelationManager extends RelationManager
{
    protected static string $relationship = 'lessons';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Lesson Basics')
                ->columnSpanFull()
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state))),

                    TextInput::make('slug')
                        ->required()
                        ->unique('lessons', 'slug', ignoreRecord: true),

                    // The "Core Architectural Decision": The Blocks Builder
                    Builder::make('blocks')->blocks([
                        Builder\Block::make('text_content')
                            ->icon('heroicon-o-book-open')
                            ->schema([
                                MarkdownEditor::make('content')
                                    ->label('Story/Instruction')
                                    ->required(),
                            ]),
                        Builder\Block::make('code_challenge')
                            ->label('Interactive Code Challenge')
                            ->icon('heroicon-o-code-bracket')
                            ->schema([
                                Select::make('language')
                                    ->options([
                                        'python' => 'Python 3 (Pyodide)',
                                        'javascript' => 'JavaScript (Native)',
                                    ])
                                    ->default('python')
                                    ->required(),

                                CodeEditor::make('initial_code')
                                    ->label('Starter Code (What the student sees)')
                                    ->required(),

                                CodeEditor::make('solution_code')
                                    ->label('Secret Solution Code (For reference)')
                                    ->helperText('This is kept secret from the student.'),

                                Repeater::make('test_cases')
                                    ->label('Validation Tests')
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('Test Name')
                                            ->placeholder('e.g., Should print "Hello World" or Calculate correct damage'),

                                        CodeEditor::make('setup_code')
                                            ->label('Setup / Injection Code (Optional)')
                                            ->helperText('Hidden code to run BEFORE evaluating the output. Good for calling the student\'s function.'),

                                        Textarea::make('expected_output')
                                            ->label('Expected Terminal Output')
                                            ->required(),

                                        Toggle::make('is_hidden')
                                            ->label('Hide test case from student?')
                                            ->default(false),
                                    ])
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['name'] ?? null),
                            ]),
                        Builder\Block::make('quiz')
                            ->label('Knowledge Encounter (Quiz)')
                            ->icon('heroicon-o-question-mark-circle')
                            ->schema([
                                Grid::make(2)->schema([
                                    Toggle::make('is_required')
                                        ->label('Mandatory Encounter')
                                        ->helperText('If checked, the student cannot advance until passed.')
                                        ->default(false),

                                    Select::make('question_type')
                                        ->options([
                                            'single' => 'Single Choice (Radio)',
                                            'multiple' => 'Multiple Choice (Checkboxes)',
                                        ])
                                        ->default('single')
                                        ->required(),
                                ]),

                                Textarea::make('question')
                                    ->label('The Question / Encounter Text')
                                    ->placeholder('The gatekeeper demands to know: What is the output of...')
                                    ->required()
                                    ->columnSpanFull(),

                                Repeater::make('answers')
                                    ->label('Possible Answers')
                                    ->schema([
                                        Grid::make(3)->schema([
                                            TextInput::make('text')
                                                ->label('Answer Text')
                                                ->required()
                                                ->columnSpan(2),

                                            Toggle::make('is_correct')
                                                ->label('Is Correct?')
                                                ->default(false)
                                                ->columnSpan(1),
                                        ]),
                                        TextInput::make('feedback')
                                            ->label('Custom Feedback (Optional)')
                                            ->helperText('Shown if the student selects this specific answer.')
                                            ->columnSpanFull(),
                                    ])
                                    ->minItems(2)
                                    ->collapsible()
                                    ->columnSpanFull(),

                                Grid::make(2)->schema([
                                    TextInput::make('xp_reward')
                                        ->label('Bonus XP')
                                        ->numeric()
                                        ->default(0)
                                        ->prefix('✨'),

                                    TextInput::make('coin_reward')
                                        ->label('Bonus Coins')
                                        ->numeric()
                                        ->default(0)
                                        ->prefix('💰'),
                                ]),
                            ])

                    ])
                    ->collapsible()
                    ->cloneable()
                    ->columnSpanFull(),
                ]),

            Section::make('Rewards & Meta')
                ->columnSpanFull()
                ->schema([
                    Toggle::make('is_boss')
                        ->label('Boss Encounter')
                        ->helperText('Mark this as a final challenge.'),

                    TextInput::make('xp_reward')
                        ->label('XP Reward')
                        ->numeric()
                        ->default(100)
                        ->prefix('✨'),

                    TextInput::make('coin_reward')
                        ->label('Coin Reward')
                        ->numeric()
                        ->default(50)
                        ->prefix('💰'),

                    TextInput::make('estimated_duration')
                        ->label('Duration (min)')
                        ->numeric()
                        ->default(15),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')
                    ->description(fn($record) => $record->is_boss ? '🔥 Boss Level' : null),

                TextColumn::make('xp_reward')
                    ->label('XP')
                    ->badge()
                    ->color('info'),

                TextColumn::make('coin_reward')
                    ->label('Coins')
                    ->badge()
                    ->color('warning'),

                IconColumn::make('is_boss')
                    ->label('Boss')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
                AssociateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DissociateAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
