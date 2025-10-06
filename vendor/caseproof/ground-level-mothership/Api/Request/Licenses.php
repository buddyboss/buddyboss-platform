<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Mothership\Api\Request;

use BuddyBossPlatform\GroundLevel\Mothership\Api\Request;
use BuddyBossPlatform\GroundLevel\Mothership\Api\Response;
/**
 * This class is used to interact with the licenses API.
 *
 * @see https://licenses.caseproof.com/help/api-reference#licenses
 */
class Licenses
{
    /**
     * Create a new license.
     *
     * @param  array $licenseData The data to create the license.
     * @return Response
     */
    public static function create(array $licenseData) : Response
    {
        return Request::post('licenses', $licenseData);
    }
    /**
     * Get all licenses.
     *
     * @param  array $params The parameters to pass to the API.
     * @return Response
     */
    public static function list(array $params = []) : Response
    {
        return Request::get('licenses', $params);
    }
    /**
     * Get a license by license key.
     *
     * @param string $licenseKey The license key.
     * @param array  $params     Additional parameters for the request.
     *
     * @return Response
     */
    public static function get(string $licenseKey, array $params = []) : Response
    {
        return Request::get('licenses/' . $licenseKey, $params);
    }
    /**
     * Update a license by license key.
     *
     * @param  string $licenseKey  The license key.
     * @param  array  $licenseData The data to update the license with.
     * @return Response
     */
    public static function update(string $licenseKey, array $licenseData) : Response
    {
        return Request::patch('licenses/' . $licenseKey, $licenseData);
    }
}
