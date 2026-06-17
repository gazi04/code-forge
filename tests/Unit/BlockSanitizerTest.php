<?php

use App\Support\BlockSanitizer;

beforeEach(function () {
    $this->sanitizer = new BlockSanitizer;
});

it('strips is_correct and feedback from quiz answers', function () {
    $blocks = [[
        'type' => 'quiz',
        'data' => [
            'answers' => [
                ['text' => 'A', 'is_correct' => true, 'feedback' => 'secret'],
                ['text' => 'B', 'is_correct' => false],
            ],
        ],
    ]];

    $result = $this->sanitizer->sanitize($blocks);

    expect($result[0]['data']['answers'][0])->toBe(['text' => 'A'])
        ->and($result[0]['data']['answers'][1])->toBe(['text' => 'B']);
});

it('replaces a sequence with shuffled value-only items and drops correct_sequence', function () {
    $blocks = [[
        'type' => 'sequence_challenge',
        'data' => ['correct_sequence' => [['value' => 'one'], ['value' => 'two']]],
    ]];

    $result = $this->sanitizer->sanitize($blocks);

    expect($result[0]['data'])->not->toHaveKey('correct_sequence')
        ->and($result[0]['data']['items'])->toHaveCount(2)
        ->and(collect($result[0]['data']['items'])->pluck('value')->sort()->values()->all())
        ->toBe(['one', 'two']);
});

it('removes correct_text and decoys from bughunt lines but keeps shuffled choices', function () {
    $blocks = [[
        'type' => 'bughunt_challenge',
        'data' => ['code_lines' => [
            ['type' => 'buggy', 'displayed_text' => 'b = 2', 'correct_text' => 'b = 3', 'decoy_1' => 'x', 'decoy_2' => 'y'],
        ]],
    ]];

    $line = $this->sanitizer->sanitize($blocks)[0]['data']['code_lines'][0];

    expect($line)->not->toHaveKey('correct_text')
        ->and($line)->not->toHaveKey('decoy_1')
        ->and($line['choices'])->toHaveCount(4)
        ->and($line['choices'])->toContain('b = 3');
});

it('splits matching pairs into separate shuffled columns and drops pairs', function () {
    $blocks = [[
        'type' => 'variable_matching_challenge',
        'data' => ['pairs' => [
            ['left_item' => 'L1', 'right_item' => 'R1'],
            ['left_item' => 'L2', 'right_item' => 'R2'],
        ]],
    ]];

    $data = $this->sanitizer->sanitize($blocks)[0]['data'];

    expect($data)->not->toHaveKey('pairs')
        ->and(collect($data['left_items'])->sort()->values()->all())->toBe(['L1', 'L2'])
        ->and(collect($data['right_items'])->sort()->values()->all())->toBe(['R1', 'R2']);
});

it('strips solution_code and hidden test outputs from code challenges', function () {
    $blocks = [[
        'type' => 'code_challenge',
        'data' => [
            'solution_code' => 'print(42)',
            'test_cases' => [
                ['name' => 'visible', 'expected_output' => '1', 'is_hidden' => false],
                ['name' => 'hidden', 'expected_output' => '2', 'setup_code' => 'x', 'is_hidden' => true],
            ],
        ],
    ]];

    $data = $this->sanitizer->sanitize($blocks)[0]['data'];

    expect($data)->not->toHaveKey('solution_code')
        ->and($data['test_cases'][0]['expected_output'])->toBe('1')
        ->and($data['test_cases'][1])->not->toHaveKey('expected_output')
        ->and($data['test_cases'][1])->not->toHaveKey('setup_code');
});
