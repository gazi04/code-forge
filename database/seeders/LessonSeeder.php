<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Database\Seeder;

class LessonSeeder extends Seeder
{
    /**
     * Create two topic-related lessons per course, each mixing
     * text content with interactive blocks.
     */
    public function run(): void
    {
        foreach ($this->lessons() as $courseSlug => $lessons) {
            $course = Course::where('slug', $courseSlug)->first();

            if ($course === null) {
                $this->command->warn("Course [{$courseSlug}] not found, skipping its lessons. Run CourseSeeder first.");

                continue;
            }

            foreach ($lessons as $lesson) {
                Lesson::firstOrCreate(
                    ['slug' => $lesson['slug']],
                    [
                        'course_id' => $course->id,
                        'name' => $lesson['name'],
                        'xp_reward' => $lesson['xp_reward'],
                        'coin_reward' => $lesson['coin_reward'],
                        'estimated_duration' => $lesson['estimated_duration'],
                        'is_boss' => $lesson['is_boss'] ?? false,
                        'blocks' => $lesson['blocks'],
                    ],
                );
            }
        }
    }

    /**
     * Lesson definitions keyed by their owning course slug.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function lessons(): array
    {
        return [
            'number-wizardry' => [
                [
                    'name' => 'Meet the Variables',
                    'slug' => 'meet-the-variables',
                    'xp_reward' => 100,
                    'coin_reward' => 20,
                    'estimated_duration' => 15,
                    'blocks' => [
                        $this->text(<<<'MD'
                        # Meet the Variables 📦

                        A **variable** is just a labelled box where a computer keeps a value.

                        ```python
                        score = 10
                        name = "Mara"
                        ```

                        The box on the left of the `=` is the **name**. The value on the right is what we put inside. Change the value any time you like — that is why we call it *variable*!
                        MD),
                        $this->quiz(
                            'The Gatekeeper\'s Riddle',
                            'What does `score = 10` do?',
                            [
                                ['Stores the number 10 in a box named score', true, 'Exactly! The name points to the value.'],
                                ['Checks if score equals 10', false, 'That would use `==`, not a single `=`.'],
                                ['Prints the number 10', false, 'Storing is not the same as printing.'],
                            ],
                        ),
                        $this->variableMatching(
                            'Match the Box to its Value',
                            'Connect each variable name with the value it most likely holds.',
                            [
                                ['player_name', '"Mara"'],
                                ['health_points', '100'],
                                ['is_alive', 'true'],
                            ],
                        ),
                    ],
                ],
                [
                    'name' => 'Operators & Order',
                    'slug' => 'operators-and-order',
                    'xp_reward' => 120,
                    'coin_reward' => 25,
                    'estimated_duration' => 18,
                    'blocks' => [
                        $this->text(<<<'MD'
                        # Operators & Order ➗

                        Computers do math with **operators**: `+`, `-`, `*`, `/`.

                        But order matters! Just like in school, multiplication and division happen **before** addition and subtraction.

                        ```
                        2 + 3 * 4   →   2 + 12   →   14
                        ```
                        MD),
                        $this->sequence(
                            'Order of Operations',
                            'Drag the steps so the computer solves `2 + 3 * 4` in the correct order.',
                            [
                                'Look at 2 + 3 * 4',
                                'Do the multiplication first: 3 * 4 = 12',
                                'Now add: 2 + 12',
                                'The answer is 14',
                            ],
                        ),
                        $this->codeChallenge(
                            'The Sum Spell',
                            'Write a function `add` that returns the sum of two numbers.',
                            "def add(a, b):\n    # your code here\n    pass",
                            "def add(a, b):\n    return a + b",
                            [
                                ['Adds 2 and 3', 'print(add(2, 3))', '5', false],
                                ['Adds 10 and 15', 'print(add(10, 15))', '25', true],
                            ],
                        ),
                    ],
                ],
            ],

            'cloud-foundations' => [
                [
                    'name' => 'What Lives in the Cloud',
                    'slug' => 'what-lives-in-the-cloud',
                    'xp_reward' => 100,
                    'coin_reward' => 20,
                    'estimated_duration' => 14,
                    'blocks' => [
                        $this->text(<<<'MD'
                        # What Lives in the Cloud ☁️

                        The "cloud" is **not** in the sky — it is a giant warehouse full of computers called **servers**.

                        When you watch a video or save a photo online, a server somewhere keeps it safe and sends it to you whenever you ask.

                        No magic. Just lots and lots of computers working together!
                        MD),
                        $this->quiz(
                            'The Sky Test',
                            'What is "the cloud" really made of?',
                            [
                                ['Many servers in big buildings', true, 'Right! The cloud is just other people\'s computers.'],
                                ['Actual clouds in the sky', false, 'It only borrows the name from the sky.'],
                                ['Your phone\'s battery', false, 'The cloud lives far away, not inside your phone.'],
                            ],
                        ),
                    ],
                ],
                [
                    'name' => 'How Computers Talk',
                    'slug' => 'how-computers-talk',
                    'xp_reward' => 120,
                    'coin_reward' => 25,
                    'estimated_duration' => 16,
                    'blocks' => [
                        $this->text(<<<'MD'
                        # How Computers Talk 🌐

                        Computers send messages over a **network**. Your computer (the **client**) asks for something, and a **server** answers.

                        Every answer comes with a small **status code** so the client knows what happened.
                        MD),
                        $this->variableMatching(
                            'The Core Matrix Linkage',
                            'Connect each status code with what it means.',
                            [
                                ['200', 'Success'],
                                ['404', 'Not Found'],
                                ['500', 'Server Error'],
                            ],
                        ),
                        $this->sequence(
                            'A Web Request Journey',
                            'Put the steps of loading a web page in the right order.',
                            [
                                'You type a web address',
                                'Your client sends a request',
                                'The server receives it',
                                'The server sends a response back',
                                'The page appears on your screen',
                            ],
                        ),
                    ],
                ],
            ],

            'data-pipelines-101' => [
                [
                    'name' => 'Raw Data, Clean Data',
                    'slug' => 'raw-data-clean-data',
                    'xp_reward' => 110,
                    'coin_reward' => 22,
                    'estimated_duration' => 16,
                    'blocks' => [
                        $this->text(<<<'MD'
                        # Raw Data, Clean Data 🧹

                        **Raw data** is messy — it has typos, blanks, and duplicates.

                        A data engineer **cleans** it so the numbers can be trusted:

                        - remove duplicates
                        - fill in or drop blanks
                        - fix wrong formats (like `12-01-2026` vs `2026-01-12`)
                        MD),
                        $this->quiz(
                            'The Janitor\'s Question',
                            'Why do we clean data before using it?',
                            [
                                ['So results are accurate and trustworthy', true, 'Yes! Garbage in, garbage out.'],
                                ['To make the file bigger', false, 'Cleaning usually makes data smaller, not bigger.'],
                                ['Because computers like tidy rooms', false, 'Fun idea, but the real reason is accuracy.'],
                            ],
                        ),
                        $this->sequence(
                            'Cleaning Steps',
                            'Arrange the cleaning steps a data engineer follows.',
                            [
                                'Collect the raw data',
                                'Remove duplicate rows',
                                'Fill in or drop missing values',
                                'Fix wrong formats',
                                'Save the clean dataset',
                            ],
                        ),
                    ],
                ],
                [
                    'name' => 'Building a Pipeline',
                    'slug' => 'building-a-pipeline',
                    'xp_reward' => 140,
                    'coin_reward' => 30,
                    'estimated_duration' => 20,
                    'blocks' => [
                        $this->text(<<<'MD'
                        # Building a Pipeline 🚰

                        A **pipeline** moves data through stages, often called **ETL**:

                        1. **Extract** — grab the data
                        2. **Transform** — clean and reshape it
                        3. **Load** — store it where it is needed

                        Pipelines run again and again, so even tiny bugs cause big problems!
                        MD),
                        $this->bugHunt(
                            'The Corrupted Pipeline',
                            'One line stops the numbers from totalling up. Find and fix it.',
                            [
                                ['clean', 'total = 0', null, null, null],
                                ['clean', 'for value in data:', null, null, null],
                                ['buggy', '    total = value', '    total = total + value', '    total == value', '    total = total - value'],
                                ['clean', 'print(total)', null, null, null],
                            ],
                        ),
                        $this->codeChallenge(
                            'Transform the Numbers',
                            'Write a function `double_all` that returns a new list with every number doubled.',
                            "def double_all(numbers):\n    # your code here\n    pass",
                            "def double_all(numbers):\n    return [n * 2 for n in numbers]",
                            [
                                ['Doubles a small list', 'print(double_all([1, 2, 3]))', '[2, 4, 6]', false],
                                ['Handles an empty list', 'print(double_all([]))', '[]', true],
                            ],
                        ),
                    ],
                ],
            ],

            'awakening-the-machine-mind' => [
                [
                    'name' => 'How Machines Learn',
                    'slug' => 'how-machines-learn',
                    'xp_reward' => 120,
                    'coin_reward' => 25,
                    'estimated_duration' => 17,
                    'blocks' => [
                        $this->text(<<<'MD'
                        # How Machines Learn 🤖

                        AI does not memorise rules — it learns from **examples**.

                        Show a model thousands of cat and dog photos, each with a label, and it slowly learns the patterns that tell them apart. We call these labelled examples the **training data**.
                        MD),
                        $this->quiz(
                            'The Oracle\'s Riddle',
                            'How does a machine learning model figure things out?',
                            [
                                ['By finding patterns in many labelled examples', true, 'Correct! More good examples means better learning.'],
                                ['By a programmer writing every rule by hand', false, 'That is traditional code, not machine learning.'],
                                ['By guessing randomly forever', false, 'It guesses at first, but it improves from the data.'],
                            ],
                        ),
                        $this->variableMatching(
                            'AI Vocabulary Bonds',
                            'Match each AI term with its meaning.',
                            [
                                ['Training data', 'Examples the model learns from'],
                                ['Model', 'The thing that makes predictions'],
                                ['Label', 'The correct answer for an example'],
                            ],
                        ),
                    ],
                ],
                [
                    'name' => 'Teaching the Oracle',
                    'slug' => 'teaching-the-oracle',
                    'xp_reward' => 150,
                    'coin_reward' => 32,
                    'estimated_duration' => 22,
                    'is_boss' => true,
                    'blocks' => [
                        $this->text(<<<'MD'
                        # Teaching the Oracle 🔮

                        A model improves by checking how wrong its guesses are and adjusting. The simplest "prediction" is just a rule we can write ourselves.

                        Let's build a tiny predictor that decides if a number is **big** or **small**.
                        MD),
                        $this->codeChallenge(
                            'The Prediction Spell',
                            'Write a function `predict` that returns "big" if the number is 50 or more, otherwise "small".',
                            "def predict(n):\n    # your code here\n    pass",
                            "def predict(n):\n    if n >= 50:\n        return \"big\"\n    return \"small\"",
                            [
                                ['Predicts big', 'print(predict(80))', 'big', false],
                                ['Predicts small', 'print(predict(12))', 'small', false],
                                ['Boundary is big', 'print(predict(50))', 'big', true],
                            ],
                        ),
                        $this->sequence(
                            'The Training Loop',
                            'Order the steps a model repeats while it learns.',
                            [
                                'Make a prediction',
                                'Compare it to the correct label',
                                'Measure how wrong it was',
                                'Adjust to do better next time',
                            ],
                        ),
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array{type: string, data: array{content: string}}
     */
    private function text(string $content): array
    {
        return [
            'type' => 'text_content',
            'data' => ['content' => $content],
        ];
    }

    /**
     * @param  array<int, array{0: string, 1: bool, 2: string}>  $answers
     * @return array{type: string, data: array<string, mixed>}
     */
    private function quiz(string $title, string $question, array $answers): array
    {
        return [
            'type' => 'quiz',
            'data' => [
                'game_title' => $title,
                'game_icon' => '❓',
                'is_required' => true,
                'question_type' => 'single',
                'question' => $question,
                'answers' => array_map(fn (array $answer): array => [
                    'text' => $answer[0],
                    'is_correct' => $answer[1],
                    'feedback' => $answer[2],
                ], $answers),
                'xp_reward' => 50,
                'coin_reward' => 10,
            ],
        ];
    }

    /**
     * @param  array<int, array{0: string, 1: string}>  $pairs
     * @return array{type: string, data: array<string, mixed>}
     */
    private function variableMatching(string $title, string $instructions, array $pairs): array
    {
        return [
            'type' => 'variable_matching_challenge',
            'data' => [
                'game_title' => $title,
                'game_icon' => '🔗',
                'instructions' => $instructions,
                'is_required' => false,
                'pairs' => array_map(fn (array $pair): array => [
                    'left_item' => $pair[0],
                    'right_item' => $pair[1],
                ], $pairs),
                'xp_reward' => 50,
                'coin_reward' => 10,
            ],
        ];
    }

    /**
     * @param  array<int, string>  $steps
     * @return array{type: string, data: array<string, mixed>}
     */
    private function sequence(string $title, string $instructions, array $steps): array
    {
        return [
            'type' => 'sequence_challenge',
            'data' => [
                'game_title' => $title,
                'game_icon' => '📜',
                'instructions' => $instructions,
                'is_required' => false,
                'correct_sequence' => array_map(fn (string $step): array => ['value' => $step], $steps),
                'xp_reward' => 50,
                'coin_reward' => 10,
            ],
        ];
    }

    /**
     * @param  array<int, array{0: string, 1: string, 2: ?string, 3: ?string, 4: ?string}>  $lines
     * @return array{type: string, data: array<string, mixed>}
     */
    private function bugHunt(string $title, string $instructions, array $lines): array
    {
        return [
            'type' => 'bughunt_challenge',
            'data' => [
                'game_title' => $title,
                'game_icon' => '🐛',
                'instructions' => $instructions,
                'is_required' => false,
                'code_lines' => array_map(fn (array $line): array => [
                    'type' => $line[0],
                    'displayed_text' => $line[1],
                    'correct_text' => $line[2],
                    'decoy_1' => $line[3],
                    'decoy_2' => $line[4],
                ], $lines),
                'xp_reward' => 60,
                'coin_reward' => 15,
            ],
        ];
    }

    /**
     * @param  array<int, array{0: string, 1: string, 2: string, 3: bool}>  $tests
     * @return array{type: string, data: array<string, mixed>}
     */
    private function codeChallenge(string $title, string $description, string $initialCode, string $solutionCode, array $tests): array
    {
        return [
            'type' => 'code_challenge',
            'data' => [
                'game_title' => $title,
                'game_icon' => '🖥️',
                'language' => 'python',
                'description' => $description,
                'initial_code' => $initialCode,
                'solution_code' => $solutionCode,
                'test_cases' => array_map(fn (array $test): array => [
                    'name' => $test[0],
                    'setup_code' => $test[1],
                    'expected_output' => $test[2],
                    'is_hidden' => $test[3],
                ], $tests),
                'xp_reward' => 60,
                'coin_reward' => 15,
            ],
        ];
    }
}
