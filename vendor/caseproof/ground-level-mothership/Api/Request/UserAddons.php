<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Mothership\Api\Request;

use BuddyBossPlatform\GroundLevel\Mothership\Api\Response;
/**
 * This class is used to interact with the user addons API.
 *
 * @link https://licenses.caseproof.com/help/api-reference#users
 */
class UserAddons extends AbstractResource
{
    /**
     * Create a new user addon.
     *
     * @param  string $userUUID The user UUID.
     * @param  array  $body     The body of the request.
     * @param  array  $params   Additional query parameters.
     * @return \GroundLevel\Mothership\Api\Response
     */
    public function create(string $userUUID, array $body, array $params = []) : Response
    {
        return $this->request->post('users/' . $userUUID . '/addons', $body, $params);
    }
    /**
     * Get all user addons.
     *
     * @param  string $userUUID The user UUID.
     * @param  array  $params   Additional query parameters.
     * @return \GroundLevel\Mothership\Api\Response
     */
    public function list(string $userUUID, array $params = []) : Response
    {
        return $this->request->get('users/' . $userUUID . '/addons', $params);
    }
    /**
     * Get a user addon by user UUID and addon UUID.
     *
     * @param  string $userUUID  The user UUID.
     * @param  string $addonUUID The addon UUID.
     * @param  array  $params    Additional query parameters.
     * @return \GroundLevel\Mothership\Api\Response
     */
    public function get(string $userUUID, string $addonUUID, array $params = []) : Response
    {
        return $this->request->get('users/' . $userUUID . '/addons/' . $addonUUID, $params);
    }
    /**
     * Update a user addon.
     *
     * @param  string $userUUID  The user UUID.
     * @param  string $addonUUID The addon UUID.
     * @param  array  $body      The body of the request.
     * @param  array  $params    Additional query parameters.
     * @return \GroundLevel\Mothership\Api\Response
     */
    public function update(string $userUUID, string $addonUUID, array $body, array $params = []) : Response
    {
        return $this->request->patch('users/' . $userUUID . '/addons/' . $addonUUID, $body, $params);
    }
}
