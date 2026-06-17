<?php

use Illuminate\Support\Facades\Schema;

function hasIndexOnColumn(string $table, string $column): bool
{
    foreach (Schema::getIndexes($table) as $index) {
        if (in_array($column, $index['columns'], true) && count($index['columns']) === 1) {
            return true;
        }
    }

    return false;
}

it('indexes lesson_id on block_submissions', function () {
    expect(hasIndexOnColumn('block_submissions', 'lesson_id'))->toBeTrue();
});

it('indexes lesson_id on lesson_submissions', function () {
    expect(hasIndexOnColumn('lesson_submissions', 'lesson_id'))->toBeTrue();
});
