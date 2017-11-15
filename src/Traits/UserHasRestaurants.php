<?php

namespace TakeawayTown\Restaurants\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Config;
use TakeawayTown\Restaurants\Events\UserJoinedRestaurant;
use TakeawayTown\Restaurants\Events\UserLeftRestaurant;
use TakeawayTown\Restaurants\Exceptions\UserNotInRestaurantException;

trait UserHasTeams
{
    /**
     * Many-to-Many relations with the user model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function restaurants()
    {
        return $this->belongsToMany( Config::get( 'restaurants.restaurant_model' ),Config::get( 'restaurants.restaurant_user_table' ), 'user_id', 'restaurant_id' )->withTimestamps();
    }

    /**
     * has-one relation with the current selected restaurant model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function currentRestaurant()
    {
        return $this->hasOne( Config::get( 'restaurants.restaurant_model' ), 'id', 'current_restaurant_id' );
    }

    /**
     * @return mixed
     */
    public function ownedRestaurants()
    {
        return $this->restaurants()->where( "owner_id", "=", $this->getKey() );
    }

    /**
     * One-to-Many relation with the invite model
     * @return mixed
     */
    public function invites()
    {
        return $this->hasMany( Config::get('restaurants.invite_model'), 'email', 'email' );
    }

    /**
     * Boot the user model
     * Attach event listener to remove the many-to-many records when trying to delete
     * Will NOT delete any records if the user model uses soft deletes.
     *
     * @return void|bool
     */
    public static function bootUserHasRestaurants()
    {
        static::deleting( function ( Model $user )
        {
            if ( !method_exists( Config::get( 'restaurants.user_model' ), 'bootSoftDeletes' ) )
            {
                $user->restaurants()->sync( [ ] );
            }
            return true;
        } );
    }


    /**
     * Returns if the user owns a restaurant
     *
     * @return bool
     */
    public function isOwner()
    {
        return ( $this->restaurants()->where( "owner_id", "=", $this->getKey() )->first() ) ? true : false;
    }

    /**
     * Returns if the user owns a restaurant
     *
     * @return bool
     */
    public function isRestaurantOwner()
    {
        return $this->isOwner();
    }

    /**
     * @param $restaurant
     * @return mixed
     */
    protected function retrieveRestaurantId( $restaurant )
    {
        if ( is_object( $restaurant ) )
        {
            $restaurant = $restaurant->getKey();
        }
        if ( is_array( $restaurant ) && isset( $restaurant[ "id" ] ) )
        {
            $restaurant = $restaurant[ "id" ];
        }
        return $restaurant;
    }

    /**
     * Returns if the user owns the given restaurant
     *
     * @param mixed $restaurant
     * @return bool
     */
    public function isOwnerOfRestaurant( $restaurant )
    {
        $restaurant_id        = $this->retrieveRestaurantId( $restaurant );
        return ( $this->resaurants()
            ->where('owner_id', $this->getKey())
            ->where('restaurant_id', $restaurant_id)->first()
        ) ? true : false;
    }

    /**
     * Alias to eloquent many-to-many relation's attach() method.
     *
     * @param mixed $restaurant
     * @param array $pivotData
     * @return $this
     */
    public function attachRestaurant( $restaurant, $pivotData = [] )
    {
        $restaurant = $this->retrieveRestaurantId( $restaurant );
        /**
         * If the user has no current restaurant,
         * use the attached one
         */
        if( is_null( $this->current_restaurant_id ) )
        {
            $this->current_restaurant_id = $restaurant;
            $this->save();

            if( $this->relationLoaded('currentRestaurant') ) {
                $this->load('currentRestaurant');
            }

        }

        // Reload relation
        $this->load('restaurants');

        if( !$this->restaurants->contains( $restaurant ) )
        {
            $this->restaurants()->attach( $restaurant, $pivotData );

            event(new UserJoinedRestaurant($this, $restaurant));

            if( $this->relationLoaded('restaurants') ) {
                $this->load('restaurants');
            }
        }
        return $this;
    }

    /**
     * Alias to eloquent many-to-many relation's detach() method.
     *
     * @param mixed $restaurant
     * @return $this
     */
    public function detachRestaurant( $restaurant )
    {
        $restaurant = $this->retrieveRestaurantId( $restaurant );
        $this->restaurants()->detach( $restaurant );

        event(new UserLeftRestaurant($this, $restaurant));

        if( $this->relationLoaded('restaurants') ) {
            $this->load('restaurants');
        }

        /**
         * If the user has no more restaurants,
         * unset the current_restaurant_id
         */
        if( $this->restaurants()->count() === 0 || $this->current_restaurant_id === $restaurant )
        {
            $this->current_restaurant_id = null;
            $this->save();

            if( $this->relationLoaded('currentRestaurant') ) {
                $this->load('currentRestaurant');
            }

        }
        return $this;
    }

    /**
     * Attach multiple restaurants to a user
     *
     * @param mixed $restaurants
     * @return $this
     */
    public function attachRestaurants( $restaurants )
    {
        foreach ( $restaurants as $restaurant )
        {
            $this->attachRestaurant( $restaurant );
        }
        return $this;
    }

    /**
     * Detach multiple restaurants from a user
     *
     * @param mixed $restaurants
     * @return $this
     */
    public function detachRestaurants( $restaurants )
    {
        foreach ( $restaurants as $restaurant )
        {
            $this->detachRestaurant( $restaurant );
        }
        return $this;
    }

    /**
     * Switch the current restaurant of the user
     *
     * @param object|array|integer $restaurant
     * @return $this
     * @throws ModelNotFoundException
     * @throws UserNotInTeamException
     */
    public function switchRestaurant( $restaurant )
    {
        if( $restaurant !== 0 && $restaurant !== null )
        {
            $restaurant = $this->retrieveRestaurantId( $restaurant );
            $restaurantModel = Config::get( 'restaurants.restaurant_model' );
            $restaurantObject = ( new $restaurantModel() )->find( $restaurant );
            if( !$restaurantObject )
            {
                $exception = new ModelNotFoundException();
                $exception->setModel( $restaurantModel );
                throw $exception;
            }
            if( !$restaurantObject->users->contains( $this->getKey() ) )
            {
                $exception = new UserNotInRestaurantException();
                $exception->setRestaurant( $restaurantObject->name );
                throw $exception;
            }
        }
        $this->current_restaurant_id = $restaurant;
        $this->save();

        if( $this->relationLoaded('currentRestaurant') ) {
            $this->load('currentRestaurant');
        }

        return $this;
    }
}
