<?php

namespace App\Filament\Resources\Lessons\Schemas;

use App\Filament\Forms\Components\DungeonGridBuilder;
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
                Grid::make(2)->schema([
                    TextInput::make('game_title')
                        ->label('Challenge Title')
                        ->placeholder('e.g., The Corrupted Terminal')
                        ->required(),

                    TextInput::make('game_icon')
                        ->label('Display Emoji Icon')
                        ->default('🖥️')
                        ->placeholder('e.g., 🖥️, ⚡, 🔬')
                        ->required(),
                ]),

                Select::make('language')
                    ->options([
                        'python' => 'Python 3 (Pyodide)',
                        'javascript' => 'JavaScript (Native)',
                    ])
                    ->default('python')
                    ->required(),

                Textarea::make('description')
                    ->label('Challenge Description')
                    ->helperText('Explain the task to the student. Shown above the code editor.')
                    ->placeholder('e.g., Write a function that returns the sum of two numbers.')
                    ->rows(3)
                    ->columnSpanFull(),

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

                Grid::make(2)->schema([
                    TextInput::make('xp_reward')
                        ->label('Bonus XP')
                        ->numeric()
                        ->default(50)
                        ->prefix('✨'),

                    TextInput::make('coin_reward')
                        ->label('Bonus Coins')
                        ->numeric()
                        ->default(10)
                        ->prefix('💰'),
                ]),
            ]);
    }

    public static function quiz(): Builder\Block
    {
        return Builder\Block::make('quiz')
            ->label('Knowledge Encounter (Quiz)')
            ->icon('heroicon-o-question-mark-circle')
            ->schema([
                Grid::make(2)->schema([
                    TextInput::make('game_title')
                        ->label('Quiz Title')
                        ->placeholder('e.g., The Gatekeeper\'s Riddle')
                        ->required(),

                    TextInput::make('game_icon')
                        ->label('Display Emoji Icon')
                        ->default('❓')
                        ->placeholder('e.g., ❓, 🧠, ⚔️')
                        ->required(),
                ]),

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
                        ->default(50)
                        ->prefix('✨'),

                    TextInput::make('coin_reward')
                        ->label('Bonus Coins')
                        ->numeric()
                        ->default(10)
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
                    TextInput::make('game_title')
                        ->label('Labyrinth Title')
                        ->placeholder('e.g., The Obsidian Maze')
                        ->required(),

                    TextInput::make('game_icon')
                        ->label('Display Emoji Icon')
                        ->default('🏃')
                        ->placeholder('e.g., 🏃, 🗺️, ⚔️')
                        ->required(),
                ]),

                Grid::make(2)->schema([
                    Toggle::make('is_required')
                        ->label('Mandatory Encounter')
                        ->default(false),

                    TextInput::make('max_commands')
                        ->label('Max Command Limit')
                        ->helperText('Leave empty for unlimited. Setting a limit forces efficient code!')
                        ->numeric(),
                ]),

                DungeonGridBuilder::make('map_layout')
                    ->label('Dungeon Grid Map Canvas Workbench')
                    ->columnSpanFull(),

                Grid::make(2)->schema([
                    TextInput::make('xp_reward')
                        ->label('Bonus XP')
                        ->numeric()
                        ->default(50)
                        ->prefix('✨'),

                    TextInput::make('coin_reward')
                        ->label('Bonus Coins')
                        ->numeric()
                        ->default(10)
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
                        ->default(50)
                        ->prefix('✨'),

                    TextInput::make('coin_reward')
                        ->label('Bonus Coins')
                        ->numeric()
                        ->default(10)
                        ->prefix('💰'),
                ]),
            ]);
    }

    public static function bugHunting(): Builder\Block
    {
        return Builder\Block::make('bughunt_challenge')
            ->label('Bug Hunt (Code Debugger)')
            ->icon('heroicon-o-bug-ant')
            ->schema([
                Grid::make(2)->schema([
                    TextInput::make('game_title')
                        ->label('Game Title / Context')
                        ->default('The Corrupted Spellbook')
                        ->required(),

                    TextInput::make('game_icon')
                        ->label('Display Emoji Icon')
                        ->default('🐛')
                        ->placeholder('e.g., 🐛, ⚡, 🛑')
                        ->required(),
                ]),

                Textarea::make('instructions')
                    ->label('Instructions for Students')
                    ->default('Analyze the code block below. Click on lines containing syntax or logical errors, then select the proper fix.')
                    ->rows(2)
                    ->required(),

                Toggle::make('is_required')
                    ->label('Mandatory Encounter')
                    ->default(false),

                // Core Engine: Line-by-line editor interface
                Repeater::make('code_lines')
                    ->label('Code Snippet Configuration')
                    ->helperText('Build the code line-by-line. Mark broken lines as "Buggy Line" to set up choice selections.')
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('type')
                                ->options([
                                    'clean' => '🟢 Clean Line',
                                    'buggy' => '🔴 Buggy Line',
                                ])
                                ->default('clean')
                                ->required()
                                ->reactive(),

                            TextInput::make('displayed_text')
                                ->label('Initial Code Line Text')
                                ->helperText('The line exactly as it appears to the student initially.')
                                ->required()
                                ->columnSpan(2),
                        ]),

                        Grid::make(3)->schema([
                            TextInput::make('correct_text')
                                ->label('Correct Replacement Text')
                                ->helperText('The absolute correct string matching the fix.')
                                ->required()
                                ->hidden(fn (callable $get) => $get('type') !== 'buggy'),

                            TextInput::make('decoy_1')
                                ->label('Decoy Option 1')
                                ->helperText('Wrong alternative choice.')
                                ->required()
                                ->hidden(fn (callable $get) => $get('type') !== 'buggy'),

                            TextInput::make('decoy_2')
                                ->label('Decoy Option 2')
                                ->helperText('Wrong alternative choice.')
                                ->required()
                                ->hidden(fn (callable $get) => $get('type') !== 'buggy'),
                        ]),
                    ])
                    ->createItemButtonLabel('Add Code Line')
                    ->minItems(1)
                    ->columnSpanFull(),

                Grid::make(2)->schema([
                    TextInput::make('xp_reward')
                        ->label('Bonus XP')
                        ->numeric()
                        ->default(50)
                        ->prefix('✨'),

                    TextInput::make('coin_reward')
                        ->label('Bonus Coins')
                        ->numeric()
                        ->default(10)
                        ->prefix('💰'),
                ]),
            ]);
    }

    public static function variableMatching(): Builder\Block
    {
        return Builder\Block::make('variable_matching_challenge')
            ->label('Variable Matching (Pair Connector)')
            ->icon('heroicon-o-link')
            ->schema([
                Grid::make(2)->schema([
                    TextInput::make('game_title')
                        ->label('Game Title / Context')
                        ->default('The Core Matrix Linkage')
                        ->required(),

                    TextInput::make('game_icon')
                        ->label('Display Emoji Icon')
                        ->default('🔗')
                        ->placeholder('e.g., 🔗, 🧪, 🔮')
                        ->required(),
                ]),

                Textarea::make('instructions')
                    ->label('Instructions for Students')
                    ->default('Connect each entity on the left side with its corresponding counterpart on the right side.')
                    ->rows(2)
                    ->required(),

                Toggle::make('is_required')
                    ->label('Mandatory Encounter')
                    ->default(false),

                // Core Engine Schema: Defines the explicit matching bonds
                Repeater::make('pairs')
                    ->label('Define Association Pairs')
                    ->helperText('Input pairs exactly as they should correspond. The engine will automatically isolate and scramble both columns for the user.')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('left_item')
                                ->label('Left Key Entity')
                                ->placeholder('e.g., standard_price or 80')
                                ->required(),

                            TextInput::make('right_item')
                                ->label('Right Match Partner')
                                ->placeholder('e.g., Float or HTTP Success')
                                ->required(),
                        ]),
                    ])
                    ->minItems(2)
                    ->createItemButtonLabel('Add New Correlation Pair')
                    ->columnSpanFull(),

                Grid::make(2)->schema([
                    TextInput::make('xp_reward')
                        ->label('Bonus XP')
                        ->numeric()
                        ->default(50)
                        ->prefix('✨'),

                    TextInput::make('coin_reward')
                        ->label('Bonus Coins')
                        ->numeric()
                        ->default(10)
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
            static::bugHunting(),
            static::variableMatching(),
        ];
    }
}
