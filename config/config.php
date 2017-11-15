<?php

return [
    'invite_model' => TakeawayTown\Restaurants\Invite::class,
    'restaurant_model' => TakeawayTown\Restaurants\Team::class,
    'team_model' => TakeawayTown\Restaurants\Team::class,
    'user_model' => config('auth.providers.users.model', App\User::class),

    'users_table' => 'users',
    'restaurants_table' => 'restaurants',
    'teams_table' => 'teams',
    'restaurant_invites_table' => 'restaurant_invites',

    'restaurant_user_table' => 'restaurant_user',
    'team_user_table' => 'team_user',

    'user_foreign_key' => 'id',
];
