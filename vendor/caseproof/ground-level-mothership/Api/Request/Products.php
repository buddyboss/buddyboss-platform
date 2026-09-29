<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Mothership\Api\Request;

use BuddyBossPlatform\GroundLevel\Mothership\Api\Response;
/**
 * This class is used to interact with the products API.
 *
 * @link https://licenses.caseproof.com/help/api-reference#products
 */
class Products extends AbstractResource
{
    /**
     * Get product by slug.
     *
     * @param string $slug   The product slug.
     * @param array  $params Additional query parameters.
     *
     * @return \GroundLevel\Mothership\Api\Response The product data.
     */
    public function get(string $slug = '', array $params = []) : Response
    {
        $endpoint = '' === $slug ? 'products' : 'products/' . $slug;
        return $this->request->get($endpoint, $params);
    }
    /**
     * Get notifications for a product.
     *
     * @param  string $slug   The product slug.
     * @param  array  $params Additional query parameters.
     * @return \GroundLevel\Mothership\Api\Response The response from the API.
     */
    public function getNotifications(string $slug, array $params = []) : Response
    {
        $endpoint = 'products/' . $slug . '/notifications';
        return $this->request->get($endpoint, $params);
    }
    /**
     * List all products.
     *
     * @param array $params Additional query parameters.
     *
     * @return \GroundLevel\Mothership\Api\Response The list of products.
     */
    public function list(array $params = []) : Response
    {
        return $this->get('', $params);
    }
    /**
     * Get product by product slug and version.
     *
     * @param string $slug    The product slug.
     * @param string $version The product version.
     * @param array  $params  Additional query parameters.
     *
     * @return \GroundLevel\Mothership\Api\Response The response from the API.
     */
    public function getVersion(string $slug, string $version, array $params = []) : Response
    {
        $endpoint = 'products/' . $slug . '/versions/' . $version;
        return $this->request->get($endpoint, $params);
    }
    /**
     * Get the latest version for a product.
     *
     * @param string $slug   The product slug.
     * @param array  $params Additional query parameters.
     *
     * @return \GroundLevel\Mothership\Api\Response The response from the API.
     */
    public function getVersionLatest(string $slug, array $params = []) : Response
    {
        $endpoint = 'products/' . $slug . '/versions/latest';
        return $this->request->get($endpoint, $params);
    }
    /**
     * Get the latest version check for a product. Requests without a valid license
     * are permitted, but may not utilize embeds.
     *
     * @param string $slug   The product slug.
     * @param array  $params Additional query parameters.
     *
     * @return \GroundLevel\Mothership\Api\Response The response from the API.
     */
    public function getVersionCheck(string $slug, array $params = []) : Response
    {
        $endpoint = 'products/' . $slug . '/versions/check';
        return $this->request->get($endpoint, $params);
    }
    /**
     * Get all versions for a product.
     *
     * @param string $slug   The product slug.
     * @param array  $params Additional query parameters.
     *
     * @return \GroundLevel\Mothership\Api\Response The response from the API.
     */
    public function getVersions(string $slug, array $params = []) : Response
    {
        $endpoint = 'products/' . $slug . '/versions';
        return $this->request->get($endpoint, $params);
    }
    /**
     * Get relations for a product.
     *
     * @param string $slug   The product slug.
     * @param array  $params Additional query parameters.
     *
     * @return \GroundLevel\Mothership\Api\Response The response from the API.
     */
    public function getRelations(string $slug, array $params = []) : Response
    {
        $endpoint = 'products/' . $slug . '/relations';
        return $this->request->get($endpoint, $params);
    }
    /**
     * Deploy a version of a product.
     *
     * @param string $slug    The product slug.
     * @param string $version The product version.
     * @param array  $params  Additional query parameters.
     *
     * @return \GroundLevel\Mothership\Api\Response The response from the API.
     */
    public function deployVersion(string $slug, string $version, array $params = []) : Response
    {
        $endpoint = 'products/' . $slug . '/versions/' . $version . '/deploy';
        return $this->request->post($endpoint, [], $params);
    }
}
