<?php

namespace Ohffs\CountsDatabaseQueries;

trait CountsDatabaseQueries
{
    protected $recordedDatabaseQueries = [];

    protected function countDatabaseQueries()
    {
        $this->recordedDatabaseQueries = [];
        \DB::listen(function ($query) {
            $this->recordedDatabaseQueries[] = [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time' => $query->time,
            ];
        });
    }

    protected function assertQueryCountEquals($expectedCount)
    {
        $this->assertCount($expectedCount, $this->recordedDatabaseQueries);
    }

    protected function assertQueryCountGreaterThan($expectedCount)
    {
        $this->assertGreaterThan($expectedCount, count($this->recordedDatabaseQueries));
    }

    protected function assertQueryCountLessThan($expectedCount)
    {
        $this->assertLessThan($expectedCount, count($this->recordedDatabaseQueries));
    }

    protected function assertQueryCountBetween($min, $max)
    {
        $this->assertGreaterThanOrEqual($min, count($this->recordedDatabaseQueries));
        $this->assertLessThanOrEqual($max, count($this->recordedDatabaseQueries));
    }
}
