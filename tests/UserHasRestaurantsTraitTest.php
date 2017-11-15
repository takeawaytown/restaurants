<?php

use TakeawayTown\Restaurants\Restaurants;

class UserHasTeamsTraitTest extends Orchestra\Testbench\TestCase
{
    protected $user;

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('restaurants.user_model', 'User');

        \Schema::create('users', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function setUp()
    {
        parent::setUp();

        $this->artisan('migrate', ['--database' => 'testing']);

        $this->user = new User();
        $this->user->name = 'Alex';
        $this->user->save();
    }

    protected function getPackageProviders($app)
    {
        return [\TakeawayTown\Restaurants\RestaurantsServiceProvider::class];
    }

    protected function getPackageAliases($app)
    {
        return [
            'Restaurants' => \TakeawayTown\Restaurants\Facades\Restaurants::class
        ];
    }

    public function testNewUserHasNoRestaurants()
    {
        $user = new User();
        $user->name = 'Alex';
        $user->save();

        $this->assertCount(0, $user->restaurants);
        $this->assertEquals(0, $user->current_restaurant_id);
        $this->assertNull($user->currentRestaurant);
        $this->assertCount(0, $user->ownedRestaurants);
        $this->assertCount(0, $user->invites);
    }

    public function testAttachingRestaurantSetsCurrentRestaurant()
    {
        $restaurant = Restaurant::create(['name' => 'Creamies']);
        $this->assertNull($this->user->currentRestaurant);

        $this->user->attachRestaurant($restaurant);

        $this->assertEquals(1, $this->user->currentRestaurants->getKey());
    }

    public function testCanAttachRestaurantToUser()
    {
        $restaurant = Restaurant::create(['name' => 'Creamies']);

        $this->user->attachRestaurant($restaurant);

        // Reload relation
        $this->assertCount(1, $this->user->restaurants);
        $this->assertEquals(Restaurant::find(1)->toArray(), $this->user->currentRestaurant->toArray());
    }

    public function testCanAttachRestaurantAsArrayToUser()
    {
        $restaurant = Restaurant::create(['name' => 'Creamies']);

        $this->user->attachRestaurant($restaurant->toArray());

        // Reload relation
        $this->assertCount(1, $this->user->restaurants);
        $this->assertEquals(Restaurant::find(1)->toArray(), $this->user->currentRestaurant->toArray());
    }
}
