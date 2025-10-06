<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Mothership\Api\Request;

use BuddyBossPlatform\GroundLevel\Mothership\Api\Request;
use BuddyBossPlatform\GroundLevel\Mothership\Api\Response;
/**
 * This class is used to interact with the user addons API.
 *
 * @see https://licenses.caseproof.com/help/api-reference#users
 */
class UserAddons
{
    /**
     * Create a new user addon.
     *
     * @param  string $userUUID  The user UUID.
     * @param  array  $addonData The data to create the user addon.
     * @return Response
     */
    public static function create(string $userUUID, array $addonData) : Response
    {
        return Request::post('users/' . $userUUID . '/addons', $addonData);
    }
    /**
     * Get all user addons.
     *
     * @param  string $userUUID The user UUID.
     * @param  array  $params   The parameters to get the user addons for.
     * @return Response
     */
    public static function list(string $userUUID, array $params = []) : Response
    {
        return Request::get('users/' . $userUUID . '/addons', $params);
    }
    /**
     * Get a user addon by user UUID and addon UUID.
     *
     * @param  string $userUUID  The user UUID.
     * @param  string $addonUUID The addon UUID.
     * @param  array  $params    The parameters to get the user addon for.
     * @return Response
     */
    public static function get(string $userUUID, string $addonUUID, array $params = []) : Response
    {
        return Request::get('users/' . $userUUID . '/addons/' . $addonUUID, $params);
    }
    /**
     * Update a user addon.
     *
     * @param  string $userUUID  The user UUID.
     * @param  string $addonUUID The addon UUID.
     * @param  array  $addonData The data to update the user addon.
     * @return Response
     */
    public static function update(string $userUUID, string $addonUUID, array $addonData) : Response
    {
        return Request::patch('users/' . $userUUID . '/addons/' . $addonUUID, $addonData);
    }
}
