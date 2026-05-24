<?php

namespace App\Filament\Resources\Lessons\Schemas;

use Filament\Forms\Components\Builder;
use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;

class LessonBlocks
{
    public static function textContent(): Builder\Block
    {
        return Builder\Block::make('text_content')
            ->icon('heroicon-o-book-open')
            ->schema([
                MarkdownEditor::make('content')
                    ->label('Story/Instruction')
                    ->required(),
            ]);
    }

    public static function codeChallenge(): Builder\Block
    {
        return Builder\Block::make('code_challenge')
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
            ]);
    }

    public static function quiz(): Builder\Block
    {
        return Builder\Block::make('quiz')
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
            ]);
    }

    public static function labyrinthGame(): Builder\Block
    {
        return Builder\Block::make('labyrinth_challenge')
            ->label('Labyrinth of Logic (Grid Challenge)')
            ->icon('heroicon-o-map')
            ->schema([
                Grid::make(2)->schema([
                    Toggle::make('is_required')
                        ->label('Mandatory Encounter')
                        ->default(false),

                    TextInput::make('max_commands')
                        ->label('Max Command Limit')
                        ->helperText('Leave empty for unlimited. Setting a limit forces efficient code!')
                        ->numeric(),
                ]),

                Textarea::make('map_layout')
                    ->label('Dungeon Grid Map Layout')
                    ->helperText('Draw your map! S = Start, E = End, # = Wall, . = Path. Separate rows with lines.')
                    ->rows(6)
                    ->default(
                        "S . . # .\n" .
                            "# # . # .\n" .
                            ". . . . .\n" .
                            ". # # # .\n" .
                            ". . . . E"
                    )
                    ->required()
                    ->columnSpanFull(),

                Grid::make(2)->schema([
                    TextInput::make('xp_reward')
                        ->label('Bonus XP')
                        ->numeric()
                        ->default(150)
                        ->prefix('✨'),

                    TextInput::make('coin_reward')
                        ->label('Bonus Coins')
                        ->numeric()
                        ->default(75)
                        ->prefix('💰'),
                ]),
            ]);
    }

    public static function sortingChallenge(): Builder\Block
    {
        return Builder\Block::make('sequence_challenge')
            ->label('Universal Sequence Challenge')
            ->icon('heroicon-o-bars-arrow-down')
            ->schema([
                Grid::make(2)->schema([
                    TextInput::make('game_title')
                        ->label('Game Title / Context')
                        ->default('The Magic Alignment')
                        ->required(),

                    TextInput::make('game_icon')
                        ->label('Display Emoji Icon')
                        ->default('📜')
                        ->placeholder('e.g., 🧪, ⚔️, 🪵')
                        ->required(),
                ]),

                Textarea::make('instructions')
                    ->label('Instructions for Students')
                    ->default('Arrange the components below into the correct logical order.')
                    ->rows(2)
                    ->required(),

                Toggle::make('is_required')
                    ->label('Mandatory Encounter')
                    ->default(false),

                // The Core Engine Data: Admin inputs the items in perfect sequential order
                Repeater::make('correct_sequence')
                    ->label('Correct Sequence Layout')
                    ->helperText('Add items in their exact CORRECT order. The engine will automatically shuffle them for students.')
                    ->schema([
                        TextInput::make('value')
                            ->label('Line / Item Value')
                            ->required(),
                    ])
                    ->minItems(2)
                    ->createItemButtonLabel('Add Sequence Step')
                    ->columnSpanFull(),

                Grid::make(2)->schema([
                    TextInput::make('xp_reward')
                        ->label('Bonus XP')
                        ->numeric()
                        ->default(120)
                        ->prefix('✨'),

                    TextInput::make('coin_reward')
                        ->label('Bonus Coins')
                        ->numeric()
                        ->default(60)
                        ->prefix('💰'),
                ]),
            ]);
    }

    public static function all(): array
    {
        return [
            static::textContent(),
            static::codeChallenge(),
            static::quiz(),
            static::labyrinthGame(),
            static::sortingChallenge(),
        ];
    }
}
