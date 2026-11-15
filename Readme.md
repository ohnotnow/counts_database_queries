# counts_database_queries
Trait for Laravel testing to count/assert about database queries

## Installing

```
composer require --dev ohffs/counts-database-queries-trait
```

## Setting up

With Pest, add the trait in `tests/Pest.php`:

```php
use Ohffs\CountsDatabaseQueries\CountsDatabaseQueries;

pest()->extend(Tests\TestCase::class)->use(CountsDatabaseQueries::class)->in('Feature');
```

Or put `use CountsDatabaseQueries;` on your base `Tests\TestCase` and every test gets it, Pest or PHPUnit.

## Checking the query count does not grow

This is the assertion to reach for.  It visits a page and counts the queries, adds more rows, visits again, and fails if the count changed.

```php
it('runs the same number of queries however much stuff there is', function () {
    $user = User::factory()->create();
    Stuff::factory()->count(3)->create();

    $this->assertQueryCountStaysTheSame(
        visiting: fn () => $this->actingAs($user->fresh())->get('/stuff')->assertOk(),
        afterAdding: fn () => Stuff::factory()->count(5)->create(['name' => 'Added later']),
    )->assertSee('Added later');
});
```

A page that runs a query per row fails with both counts, and the queries behind the difference:

```
Ran 6 queries, then 11 after adding more rows.

Queries that ran a different number of times:
  3 -> 8  select * from "comments" where "stuff_id" = ? limit 1
```

It catches the things `Model::preventLazyLoading()` does not: a query written out inside a loop, the same query run several times in one request, and a lazy load on a model that was loaded on its own.

### Check the new rows appear

The assertion only compares two numbers.  If the rows you add never reach the page (they belong to another team, a visibility rule hides them, they fall onto page two of a paginated list) the count stays the same and the test passes on a page that may still run a query per row.

So it returns whatever the second visit returned, and you can chain a check that the new rows are there, like the `assertSee()` above.  On a paginated page, keep the total number of rows within one page.

### Sign in a freshly loaded user for each visit

`actingAs($user)` keeps the same user object for every request in a test.  Anything Eloquent remembered on that object during the first visit (a loaded relationship, say) is reused by the second, so the second visit runs fewer queries than a real request would.

So load the user inside the `visiting` closure, with `$user->fresh()` or `User::find($id)`, as in the example above.  The assertion cannot do this for you.

It fails when the second count is lower as well as when it is higher, because a lower count usually means something was remembered between the two visits.

## Counting by hand

```php
it('shows the stuff page', function () {
    $user = User::factory()->create();
    Stuff::factory()->count(3)->create();

    // you'd usually want to start counting only after all your setup calls
    $this->countDatabaseQueries();

    $this->actingAs($user)->get('/stuff')->assertOk();

    $this->assertQueryCountEquals(6);
    $this->assertQueryCountBetween(5, 7);
    $this->assertQueryCountGreaterThan(5);
    $this->assertQueryCountLessThan(7);
    dd($this->getDatabaseQueries());
});
```

These compare against a fixed number, so they are for a quick look while you work or a rough ceiling.  They will not tell you that the count grows with the number of rows: a page that runs three queries per row passes `assertQueryCountEquals()` for as long as the test keeps the same number of rows.

The count is a running total from the first `countDatabaseQueries()` call.  To start again from zero part way through a test, call `$this->resetDatabaseQueryCount()`.  `countQueriesDuring()` and `assertQueryCountStaysTheSame()` leave the total alone: the queries they run are added to it.

`countQueriesDuring()` runs a closure and returns how many queries it ran:

```php
$count = $this->countQueriesDuring(fn () => $this->actingAs($user)->get('/stuff'));
```

If all you want is an exact count, Laravel has `$this->expectsDatabaseQueryCount(6)` built in.

## For your CLAUDE.md

If you work with coding agents, this is a block to paste into your project's `CLAUDE.md` (or equivalent):

```markdown
### Query counts

The number of queries a page or action runs must not grow with the number of rows it shows, unless the user has agreed to it.  A query inside a loop (or inside a method that is called in a loop) is the same problem as lazy loading, even though Laravel will not complain about it.  The same goes for calling a method that runs a query several times in one request: make it a relationship so it can be eager loaded and is remembered.

If the application's AppServiceProvider does not have `Model::preventLazyLoading(! app()->isProduction());` in its boot method, suggest it to the user.  It only catches lazy loads, and only on models that were loaded alongside at least one other, which is why the test below is still needed.

Any page or action that lists rows gets one test using `assertQueryCountStaysTheSame()` from the `CountsDatabaseQueries` trait: visit it, add more rows, visit it again.  Check the new rows appear on the second visit (chain an `assertSee()` for one of them onto the assertion), or rows that never reach the page leave the test green for the wrong reason.  Sign in a freshly loaded user for each visit (`$user->fresh()`), or what Eloquent remembered from the first visit hides the problem.

Sometimes there is no clean way to keep the count flat.  That is the user's decision, not yours.  Before leaving a per-row query in, and equally before twisting the code to avoid one, stop work and ask.  Put the question on its own at the top of your reply and wait for the answer before carrying on.  A line in a summary, a footnote or a list of "things to note" does not count as asking.  Do not judge whether a query count is "fine" from local timings.
```

## Running the tests

```
composer install
vendor/bin/pest
```
