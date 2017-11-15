<?php
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable {
    use \TakeawayTown\Restaurants\Traits\UserHasRestaurants;
}
