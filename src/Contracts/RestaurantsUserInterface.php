<?php

namespace TakeawayTown\Restaurants\Contracts;

interface RestaurantsUserInterface
{
    /**
     * Many-to-Many relations with the user model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function restaurants();

    /**
     * Many-to-Many relations with the user model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function teams();

    /**
     * has-one relation with the current selected team model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function currentRestaurant();

    /**
     * has-one relation with the current selected team model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function currentTeam();

    /**
     * One-to-Many relation with the invite model
     * @return mixed
     */
    public function invites();


    /**
     * Returns if the user owns a team
     *
     * @return bool
     */
    public function isOwner();


    /**
     * Returns if the user owns the given restaurant
     *
     * @param mixed $restaurant
     * @return bool
     */
    public function isOwnerOfRestaurant( $restaurant );

    /**
     * Returns if the user owns the given team
     *
     * @param mixed $team
     * @return bool
     */
    public function isOwnerOfTeam( $team );

    /**
     * Alias to eloquent many-to-many relation's attach() method.
     *
     * @param mixed $restaurant
     * @param array $pivotData
     */
    public function attachRestaurant( $restaurant, $pivotData = [] );

    /**
     * Alias to eloquent many-to-many relation's attach() method.
     *
     * @param mixed $team
     * @param array $pivotData
     */
    public function attachTeam( $team, $pivotData = [] );

    /**
     * Alias to eloquent many-to-many relation's detach() method.
     *
     * @param mixed $restaurant
     */
    public function detachRestaurant( $restaurant );

    /**
     * Alias to eloquent many-to-many relation's detach() method.
     *
     * @param mixed $team
     */
    public function detachTeam( $team );

    /**
     * Attach multiple teams to a user
     *
     * @param mixed $teams
     */
    public function attachRestaurants( $restaurants );

    /**
     * Attach multiple teams to a user
     *
     * @param mixed $teams
     */
    public function attachTeams( $teams );

    /**
     * Detach multiple restaurants from a user
     *
     * @param mixed $restaurants
     */
    public function detachRestaurants( $restaurants );

    /**
     * Detach multiple teams from a user
     *
     * @param mixed $teams
     */
    public function detachTeams( $teams );

    /**
     * Switch the current restaurant of the user
     *
     * @param object|array|integer $team
     * @throws ModelNotFoundException
     * @throws UserNotInTeamException
     */
    public function switchRestaurant( $restaurant );

    /**
     * Switch the current team of the user
     *
     * @param object|array|integer $team
     * @throws ModelNotFoundException
     * @throws UserNotInTeamException
     */
    public function switchTeam( $team );
}
