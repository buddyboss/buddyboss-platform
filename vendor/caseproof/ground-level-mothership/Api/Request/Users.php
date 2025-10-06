<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Mothership\Api\Request;

use BuddyBossPlatform\GroundLevel\Mothership\Api\Request;
use BuddyBossPlatform\GroundLevel\Mothership\Api\Response;
/**
 * This class is used to interact with the users API.
 *
 * @see https://licenses.caseproof.com/help/api-reference#users
 */
class Users
{
    /**
     * Create a new user.
     *
     * @param  array $userData The data to create the user.
     * @return Response
     */
    public static function create(array $userData) : Response
    {
        return Request::post('users', $userData);
    }
    /**
     * Get all users.
     *
     * @param  array $params The parameters to get the users for.
     * @return Response
     */
    public static function list(array $params = []) : Response
    {
        return Request::get('users', $params);
    }
    /**
     * Get a user by user UUID.
     *
     * @param  string $userUUID The user UUID.
     * @param  array  $args     Additional arguments for the request.
     * @return Response
     */
    public static function get(string $userUUID, array $args = []) : Response
    {
        return Request::get('users/' . $userUUID, $args);
    }
    /**
     * Update a user.
     *
     * @param  string $userUUID The user UUID.
     * @param  array  $userData The data to update the user.
     * @return Response
     */
    public static function update(string $userUUID, array $userData) : Response
    {
        return Request::patch('users/' . $userUUID, $userData);
    }
    /**
     * Get a user by email.
     *
     * @param string $email The email of the user.
     * @param array  $args  Additional arguments for the request.
     *
     * @return Response
     */
    public static function getByEmail(string $email, array $args = []) : Response
    {
        $users = Request::get('users', \array_merge($args, ['search' => $email]));
        if ($users->isSuccess()) {
            if (\count($users->users) > 0) {
                $users = new Response($users->users[0]);
            } else {
                $users = new Response(null, 'No users found matching email: ' . $email, 404);
            }
        }
        return $users;
    }
    /**
     * Update a user by email.
     *
     * @param  string $email    The email of the user.
     * @param  array  $userData The data to update the user.
     * @return Response
     */
    public static function updateByEmail(string $email, array $userData) : Response
    {
        $user = self::getByEmail($email);
        if ($user->isError()) {
            return $user;
        }
        return self::update($user->uuid, $userData);
    }
}
