<?php

namespace Ohffs\CountsDatabaseQueries;

use Illuminate\Support\Facades\DB;

trait CountsDatabaseQueries
{
    protected function countDatabaseQueries()
    {
        DB::enableQueryLog();
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
}
