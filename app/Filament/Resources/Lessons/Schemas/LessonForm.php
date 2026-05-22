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
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class LessonForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema ->components([
            Grid::make(1)->schema([
                Section::make('Lesson Content')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state))),
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
                Section::make('Quest Rewards & Logic')
                    ->schema([
                        Select::make('course_id')
                            ->relationship('course', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('slug')
                            ->required()
                            ->unique('lessons', 'slug', ignoreRecord: true),
                        Grid::make(2)->schema([
                            TextInput::make('xp_reward')
                                ->numeric()
                                ->default(100)
                                ->prefix('✨'),
                            TextInput::make('coin_reward')
                                ->numeric()
                                ->default(50)
                                ->prefix('💰'),
                        ]),
                        TextInput::make('estimated_duration')
                            ->label('Time Estimate (min)')
                            ->numeric()
                            ->required(),
                        Toggle::make('is_boss')
                            ->label('Boss Encounter')
                            ->onIcon('heroicon-m-fire')
                            ->offIcon('heroicon-m-bolt')
                            ->helperText('Boss levels usually grant higher rewards.'),
                    ]),
            ])
                ->columnSpanFull(),
        ]);
    }
}
