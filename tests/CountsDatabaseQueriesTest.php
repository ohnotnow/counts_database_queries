<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\ExpectationFailedException;

beforeEach(function () {
    Schema::create('things', function (Blueprint $table) {
        $table->id();
        $table->string('name');
    });

    addThings(2);
});

function addThings(int $count): void
{
    DB::table('things')->insert(array_fill(0, $count, ['name' => 'thing']));
}

it('passes when the query count is the same after more rows are added', function () {
    $this->assertQueryCountStaysTheSame(
        visiting: fn () => DB::table('things')->get(),
        afterAdding: fn () => addThings(5),
    );
});

it('fails with both counts when a visit runs one query per row', function () {
    $visiting = function () {
        foreach (DB::table('things')->get() as $thing) {
            DB::table('things')->find($thing->id);
        }
    };

    expect(fn () => $this->assertQueryCountStaysTheSame(
        visiting: $visiting,
        afterAdding: fn () => addThings(5),
    ))->toThrow(ExpectationFailedException::class, 'Ran 3 queries, then 8 after adding more rows.');
});

it('fails when the second visit runs fewer queries than the first', function () {
    $visits = 0;

    $visiting = function () use (&$visits) {
        DB::table('things')->get();

        if ($visits++ === 0) {
            DB::table('things')->count();
        }
    };

    expect(fn () => $this->assertQueryCountStaysTheSame(
        visiting: $visiting,
        afterAdding: fn () => addThings(5),
    ))->toThrow(ExpectationFailedException::class, 'Ran 2 queries, then 1 after adding more rows.');
});

it('does not include queries from before a reset', function () {
    $this->countDatabaseQueries();
    DB::table('things')->get();
    DB::table('things')->count();
    $this->assertQueryCountEquals(2);

    $this->resetDatabaseQueryCount();
    DB::table('things')->get();
    $this->assertQueryCountEquals(1);
});

it('keeps a running total when counting is started more than once', function () {
    $this->countDatabaseQueries();
    DB::table('things')->get();
    DB::table('things')->count();

    $this->countDatabaseQueries();
    DB::table('things')->get();

    $this->assertQueryCountEquals(3);
});

it('returns what the second visit returned', function () {
    $result = $this->assertQueryCountStaysTheSame(
        visiting: fn () => DB::table('things')->count(),
        afterAdding: fn () => addThings(5),
    );

    expect($result)->toBe(7);
});
