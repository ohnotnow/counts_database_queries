<?php

use Ohffs\CountsDatabaseQueries\CountsDatabaseQueries;
use Orchestra\Testbench\TestCase;

pest()->extend(TestCase::class)->use(CountsDatabaseQueries::class)->in(__DIR__);
