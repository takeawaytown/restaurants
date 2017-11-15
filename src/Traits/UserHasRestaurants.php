<?php

namespace TakeawayTown\Restaurants\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Config;
use Mpociot\Teamwork\Events\UserJoinedTeam;
use Mpociot\Teamwork\Events\UserLeftTeam;
use Mpociot\Teamwork\Exceptions\UserNotInTeamException;

trait UserHasTeams
{
    /**
     * Many-to-Many relations with the user model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function teams()
    {
        return $this->belongsToMany( Config::get( 'restaurants.team_model' ),Config::get( 'restaurants.team_user_table' ), 'user_id', 'team_id' )->withTimestamps();
    }

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
        return ( $this->teams()->where( "owner_id", "=", $this->getKey() )->first() ) ? true : false;
    }

    /**
     * Returns if the user owns a team
     *
     * @return bool
     */
    public function isTeamOwner()
    {
        return ( $this->teams()->where( "owner_id", "=", $this->getKey() )->first() ) ? true : false;
    }

    /**
     * Returns if the user owns a restaurant
     *
     * @return bool
     */
    public function isRestaurantOwner()
    {
        return ( $this->restaurants()->where( "owner_id", "=", $this->getKey() )->first() ) ? true : false;
    }

    /**
     * @param $team
     * @return mixed
     */
    protected function retrieveTeamId( $team )
    {
        if ( is_object( $team ) )
        {
            $team = $team->getKey();
        }
        if ( is_array( $team ) && isset( $team[ "id" ] ) )
        {
            $team = $team[ "id" ];
        }
        return $team;
    }

    /**
     * @param $team
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
     * Returns if the user owns the given team
     *
     * @param mixed $team
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
     * Returns if the user owns the given team
     *
     * @param mixed $team
     * @return bool
     */
    public function isOwnerOfTeam( $team )
    {
        $team_id        = $this->retrieveTeamId( $team );
        return ( $this->teams()
            ->where('owner_id', $this->getKey())
            ->where('team_id', $team_id)->first()
        ) ? true : false;
    }

    /**
     * Alias to eloquent many-to-many relation's attach() method.
     *
     * @param mixed $team
     * @param array $pivotData
     * @return $this
     */
    public function attachTeam( $team, $pivotData = [] )
    {
        $team        = $this->retrieveTeamId( $team );
        /**
         * If the user has no current team,
         * use the attached one
         */
        if( is_null( $this->current_team_id ) )
        {
            $this->current_team_id = $team;
            $this->save();

            if( $this->relationLoaded('currentTeam') ) {
                $this->load('currentTeam');
            }

        }

        // Reload relation
        $this->load('teams');

        if( !$this->teams->contains( $team ) )
        {
            $this->teams()->attach( $team, $pivotData );

            event(new UserJoinedTeam($this, $team));

            if( $this->relationLoaded('teams') ) {
                $this->load('teams');
            }
        }
        return $this;
    }

    /**
     * Alias to eloquent many-to-many relation's attach() method.
     *
     * @param mixed $team
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
     * @param mixed $team
     * @return $this
     */
    public function detachTeam( $team )
    {
        $team        = $this->retrieveTeamId( $team );
        $this->teams()->detach( $team );

        event(new UserLeftTeam($this, $team));

        if( $this->relationLoaded('teams') ) {
            $this->load('teams');
        }

        /**
         * If the user has no more teams,
         * unset the current_team_id
         */
        if( $this->teams()->count() === 0 || $this->current_team_id === $team )
        {
            $this->current_team_id = null;
            $this->save();

            if( $this->relationLoaded('currentTeam') ) {
                $this->load('currentTeam');
            }

        }
        return $this;
    }

    /**
     * Alias to eloquent many-to-many relation's detach() method.
     *
     * @param mixed $team
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
         * If the user has no more teams,
         * unset the current_team_id
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
     * Attach multiple teams to a user
     *
     * @param mixed $teams
     * @return $this
     */
    public function attachTeams( $teams )
    {
        foreach ( $teams as $team )
        {
            $this->attachTeam( $team );
        }
        return $this;
    }

    /**
     * Attach multiple teams to a user
     *
     * @param mixed $teams
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
     * Detach multiple teams from a user
     *
     * @param mixed $teams
     * @return $this
     */
    public function detachTeams( $teams )
    {
        foreach ( $teams as $team )
        {
            $this->detachTeam( $team );
        }
        return $this;
    }

    /**
     * Detach multiple teams from a user
     *
     * @param mixed $teams
     * @return $this
     */
    public function detachRestaurant( $restaurants )
    {
        foreach ( $restaurants as $restaurant )
        {
            $this->detachRestaurant( $restaurant );
        }
        return $this;
    }

    /**
     * Switch the current team of the user
     *
     * @param object|array|integer $team
     * @return $this
     * @throws ModelNotFoundException
     * @throws UserNotInTeamException
     */
    public function switchTeam( $team )
    {
        if( $team !== 0 && $team !== null )
        {
            $team        = $this->retrieveTeamId( $team );
            $teamModel   = Config::get( 'teamwork.team_model' );
            $teamObject  = ( new $teamModel() )->find( $team );
            if( !$teamObject )
            {
                $exception = new ModelNotFoundException();
                $exception->setModel( $teamModel );
                throw $exception;
            }
            if( !$teamObject->users->contains( $this->getKey() ) )
            {
                $exception = new UserNotInTeamException();
                $exception->setTeam( $teamObject->name );
                throw $exception;
            }
        }
        $this->current_team_id = $team;
        $this->save();

        if( $this->relationLoaded('currentTeam') ) {
            $this->load('currentTeam');
        }

        return $this;
    }

    /**
     * Switch the current restaurant of the user
     *
     * @param object|array|integer $team
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
