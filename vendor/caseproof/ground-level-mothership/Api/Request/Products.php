<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Mothership\Api\Request;

use BuddyBossPlatform\GroundLevel\Mothership\Api\Request;
use BuddyBossPlatform\GroundLevel\Mothership\Api\Response;
/**
 * This class is used to interact with the products API.
 *
 * @see https://licenses.caseproof.com/docs/api#products
 */
class Products
{
    /**
     * Get product by slug.
     *
     * @param string $slug The product slug.
     * @param array  $args Additional arguments for the request.
     *
     * @return mixed The product data.
     */
    public static function get(string $slug = '', array $args = [])
    {
        $endpoint = 'products/' . $slug;
        $productData = Request::get($endpoint, $args);
        return $productData;
    }
    /**
     * Get notifications for a product.
     *
     * @param  string $slug The product slug.
     * @param  array  $args Additional arguments for the request.
     * @return \GroundLevel\Mothership\Api\Response The response from the API.
     */
    public static function getNotifications(string $slug, array $args = []) : Response
    {
        $endpoint = 'products/' . $slug . '/notifications';
        return Request::get($endpoint, $args);
    }
    /**
     * List all products.
     *
     * @param array $args Additional arguments for the request.
     *
     * @return mixed The list of products.
     */
    public static function list(array $args = [])
    {
        return self::get('', $args);
    }
    /**
     * Get product by product slug and version.
     *
     * @param string $slug    The product slug.
     * @param string $version The product version.
     * @param array  $args    Additional arguments for the request.
     *
     * @return Response The response from the API.
     */
    public static function getVersion(string $slug, string $version, array $args = []) : Response
    {
        $endpoint = 'products/' . $slug . '/versions/' . $version;
        return Request::get($endpoint, $args);
    }
    /**
     * Get the latest version check for a product. Requests without a valid license
     * are permitted, but may not utilize embeds.
     *
     * @param string $slug The product slug.
     * @param array  $args Additional arguments for the request.
     *
     * @return Response The response from the API.
     */
    public static function getVersionCheck(string $slug, array $args = []) : Response
    {
        $endpoint = 'products/' . $slug . '/versions/check';
        return Request::get($endpoint, $args);
    }
    /**
     * Get all versions for a product.
     *
     * @param string $slug The product slug.
     * @param array  $args Additional arguments for the request.
     *
     * @return Response The response from the API.
     */
    public static function getVersions(string $slug, array $args = []) : Response
    {
        $endpoint = 'products/' . $slug . '/versions';
        return Request::get($endpoint, $args);
    }
    /**
     * Get relations for a product.
     *
     * @param string $slug The product slug.
     * @param array  $args Additional arguments for the request.
     *
     * @return Response The response from the API.
     */
    public static function getRelations(string $slug, array $args = []) : Response
    {
        $endpoint = 'products/' . $slug . '/relations';
        return Request::get($endpoint, $args);
    }
    /**
     * Deploy a version of a product.
     *
     * @param string $slug    The product slug.
     * @param string $version The product version.
     *
     * @return Response The response from the API.
     */
    public static function deployVersion(string $slug, string $version) : Response
    {
        $endpoint = 'products/' . $slug . '/versions/' . $version . '/deploy';
        return Request::post($endpoint);
    }
}
