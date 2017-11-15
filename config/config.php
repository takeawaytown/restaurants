<?php

return [
    'invite_model' => TakeawayTown\Restaurants\Invite::class,
    'team_model' => TakeawayTown\Restaurants\Team::class,
    'user_model' => config('auth.providers.users.model', App\User::class),

    'users_table' => 'users',
    'teams_table' => 'teams',
    'team_invites_table' => 'team_invites',

    'team_user_table' => 'team_user',

    'user_foreign_key' => 'id',
];
