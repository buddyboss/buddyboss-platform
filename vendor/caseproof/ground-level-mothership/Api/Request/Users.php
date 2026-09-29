<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Mothership\Api\Request;

use BuddyBossPlatform\GroundLevel\Mothership\Api\Response;
/**
 * This class is used to interact with the users API.
 *
 * @link https://licenses.caseproof.com/help/api-reference#users
 */
class Users extends AbstractResource
{
    /**
     * Create a new user.
     *
     * @param  array $body   The body of the request.
     * @param  array $params Additional query parameters.
     * @return \GroundLevel\Mothership\Api\Response
     */
    public function create(array $body, array $params = []) : Response
    {
        return $this->request->post('users', $body, $params);
    }
    /**
     * Get all users.
     *
     * @param  array $params Additional query parameters.
     * @return \GroundLevel\Mothership\Api\Response
     */
    public function list(array $params = []) : Response
    {
        return $this->request->get('users', $params);
    }
    /**
     * Get a user by user UUID.
     *
     * @param  string $userUUID The user UUID.
     * @param  array  $params   Additional query parameters.
     * @return \GroundLevel\Mothership\Api\Response
     */
    public function get(string $userUUID, array $params = []) : Response
    {
        return $this->request->get('users/' . $userUUID, $params);
    }
    /**
     * Update a user.
     *
     * @param  string $userUUID The user UUID.
     * @param  array  $body     The body of the request.
     * @param  array  $params   Additional query parameters.
     * @return \GroundLevel\Mothership\Api\Response
     */
    public function update(string $userUUID, array $body, array $params = []) : Response
    {
        return $this->request->patch('users/' . $userUUID, $body, $params);
    }
    /**
     * Get a user by email.
     *
     * @param string $email  The email of the user.
     * @param array  $params Additional query parameters.
     *
     * @return \GroundLevel\Mothership\Api\Response
     */
    public function getByEmail(string $email, array $params = []) : Response
    {
        $users = $this->request->get('users', \array_merge($params, ['search' => $email]));
        if ($users->isSuccess()) {
            $userList = $users->getData('users', []);
            if (\count($userList) > 0) {
                $users = new Response($userList[0]);
            } else {
                $users = new Response((object) ['message' => 'No users found matching email: ' . $email], 404);
            }
        }
        return $users;
    }
    /**
     * Update a user by email.
     *
     * @param  string $email  The email of the user.
     * @param  array  $body   The body of the request.
     * @param  array  $params Additional query parameters.
     * @return \GroundLevel\Mothership\Api\Response
     */
    public function updateByEmail(string $email, array $body, array $params = []) : Response
    {
        $user = $this->getByEmail($email, $params);
        $uuid = $user->getData('uuid');
        if ($user->isError() || null === $uuid) {
            return $user;
        }
        return $this->update($uuid, $body, $params);
    }
}
