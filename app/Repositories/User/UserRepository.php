<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\BaseRepository;

class UserRepository extends BaseRepository
{
    /**
     * Return searchable fields
     *
     * @return array
     */
    public static function getFieldsSearchable()
    {
        return [
            'id',
            'name',
            'email',
            'email_verified_at',
            'password',
            'primary_role',
            'remember_token'
        ];
    }

    /**
     * Configure the Model
     **/
    public static function model()
    {
        return User::class;
    }
}
