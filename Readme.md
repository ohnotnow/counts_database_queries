# counts_database_queries
Trait for Laravel testing to count/assert about database queries

## Installing

```
composer require ohffs/counts-database-queries-trait
```

## Usage

```php
<?php

namespace Tests\Feature;

use Ohffs\CountsDatabaseQueries\CountsDatabaseQueries;

class MyAmazingTest extends TestCase
{
    use CountsDatabaseQueries;

    /** @test */
    public function we_can_see_the_webpage()
    {
        $user = User::factory()->create();
        $stuff = Stuff::factory()->count(3)->create();
 
        // you'd usually want to start counting only after all your setup calls
        $this->countDatabaseQueries();

        $response = $this->actingAs($user)->get('/stuff');

        $response->assertOk();
        $response->assertSee('Stuff');
        $this->assertQueryCountEquals(6);
        $this->assertQueryCountBetween(5, 7);
        $this->assertQueryCountGreaterThan(5);
        $this->assertQueryCountLessThan(7);
        dd($this->recordedDatabaseQueries);
    }
}
```

