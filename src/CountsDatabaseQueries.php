<?php

namespace Ohffs\CountsDatabaseQueries;

use Illuminate\Support\Facades\DB;

trait CountsDatabaseQueries
{
    protected function countDatabaseQueries()
    {
        DB::enableQueryLog();
    }

    protected function resetDatabaseQueryCount()
    {
        DB::flushQueryLog();
    }

    protected function getDatabaseQueries()
    {
        return DB::getQueryLog();
    }

    protected function assertQueryCountEquals($expectedCount)
    {
        $this->assertCount($expectedCount, DB::getQueryLog());
    }

    protected function assertQueryCountGreaterThan($expectedCount)
    {
        $this->assertGreaterThan($expectedCount, count(DB::getQueryLog()));
    }

    protected function assertQueryCountLessThan($expectedCount)
    {
        $this->assertLessThan($expectedCount, count(DB::getQueryLog()));
    }

    protected function assertQueryCountBetween($min, $max)
    {
        $this->assertGreaterThanOrEqual($min, count(DB::getQueryLog()));
        $this->assertLessThanOrEqual($max, count(DB::getQueryLog()));
    }

    protected function assertQueryCountStaysTheSame(callable $visiting, callable $afterAdding)
    {
        $before = $this->countQueriesDuring($visiting);

        $afterAdding();

        $secondVisit = null;
        $after = $this->countQueriesDuring(function () use ($visiting, &$secondVisit) {
            $secondVisit = $visiting();
        });

        $this->assertSame($before, $after, "Ran {$before} queries, then {$after} after adding more rows.");

        return $secondVisit;
    }

    protected function countQueriesDuring(callable $callback): int
    {
        $this->countDatabaseQueries();
        $this->resetDatabaseQueryCount();

        $callback();

        return count(DB::getQueryLog());
    }
}
