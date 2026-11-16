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

    protected function assertQueryCountStaysTheSame(callable $visit, callable $revisitAfterChanging)
    {
        $before = $this->queriesDuring($visit);

        $revisitAfterChanging();

        $secondVisit = null;
        $after = $this->queriesDuring(function () use ($visit, &$secondVisit) {
            $secondVisit = $visit();
        });

        $this->assertSame(count($before), count($after), $this->queryCountChangedMessage($before, $after));

        return $secondVisit;
    }

    protected function countQueriesDuring(callable $callback): int
    {
        return count($this->queriesDuring($callback));
    }

    private function queriesDuring(callable $callback): array
    {
        $this->countDatabaseQueries();
        $alreadyRun = count(DB::getQueryLog());

        $callback();

        return array_column(array_slice(DB::getQueryLog(), $alreadyRun), 'query');
    }

    private function queryCountChangedMessage(array $before, array $after): string
    {
        $message = sprintf('Ran %d queries, then %d on the second visit.', count($before), count($after));

        // an eager load lists its ids in the query, so it reads differently on each visit
        $withoutIdLists = fn (string $query) => preg_replace('/\bin \([^()]*\)/i', 'in (...)', $query);

        $before = array_count_values(array_map($withoutIdLists, $before));
        $after = array_count_values(array_map($withoutIdLists, $after));
        $changed = [];

        foreach (array_keys($before + $after) as $query) {
            $was = $before[$query] ?? 0;
            $now = $after[$query] ?? 0;

            if ($was !== $now) {
                $changed[] = "  {$was} -> {$now}  {$query}";
            }
        }

        return $message."\n\nQueries that ran a different number of times:\n".implode("\n", $changed);
    }
}
