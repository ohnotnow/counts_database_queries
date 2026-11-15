<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\ExpectationFailedException;

beforeEach(function () {
    Schema::create('things', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->foreignId('parent_id')->nullable();
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

it('lists the queries that ran a different number of times when it fails', function () {
    $visiting = function () {
        foreach (DB::table('things')->get() as $thing) {
            DB::table('things')->find($thing->id);
        }
    };

    expect(fn () => $this->assertQueryCountStaysTheSame(
        visiting: $visiting,
        afterAdding: fn () => addThings(5),
    ))->toThrow(ExpectationFailedException::class, '2 -> 7  select * from "things" where "id" = ? limit 1');
});

class Thing extends Model
{
    public function children()
    {
        return $this->hasMany(Thing::class, 'parent_id');
    }
}

it('does not list an eager load just because its list of ids grew', function () {
    $visiting = function () {
        foreach (Thing::with('children')->get() as $thing) {
            DB::table('things')->find($thing->id);
        }
    };

    expect(fn () => $this->assertQueryCountStaysTheSame(
        visiting: $visiting,
        afterAdding: fn () => addThings(5),
    ))->toThrow(function (ExpectationFailedException $e) {
        expect($e->getMessage())->not->toContain('parent_id');
    });
});

it('leaves a running total alone when counting during a closure', function () {
    $this->countDatabaseQueries();
    DB::table('things')->get();

    $count = $this->countQueriesDuring(fn () => DB::table('things')->count());

    expect($count)->toBe(1);
    $this->assertQueryCountEquals(2);
});
