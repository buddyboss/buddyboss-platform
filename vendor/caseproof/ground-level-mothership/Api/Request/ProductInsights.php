<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Mothership\Api\Request;

use BuddyBossPlatform\GroundLevel\Mothership\Api\Request;
use BuddyBossPlatform\GroundLevel\Mothership\Api\Response;
/**
 * This class is used to interact with the product insights API.
 *
 * @link https://licenses.caseproof.com/help/api-reference#product-insights
 */
class ProductInsights
{
    /**
     * Get the product insights endpoint.
     *
     * @param string $productSlug The slug of the product.
     * @param string $endpoint    The endpoint to get the product insights for.
     *
     * @return string The product insights endpoint.
     */
    private static function getEndpoint(string $productSlug, string $endpoint) : string
    {
        return "products/{$productSlug}/insights/{$endpoint}";
    }
    /**
     * Send demographics data to the API.
     *
     * @link https://licenses.caseproof.com/help/api-reference#product-insights-POSTapi-v1-products--product_slug--insights-demographics
     *
     * @param string $productSlug The slug of the product.
     * @param array  $data        The data to send to the API.
     *
     * @return \GroundLevel\Mothership\Api\Response The response from the API.
     */
    public static function demographics(string $productSlug, array $data = []) : Response
    {
        return Request::post(self::getEndpoint($productSlug, 'demographics'), $data);
    }
    /**
     * Send NPS data to the API.
     *
     * @link https://licenses.caseproof.com/help/api-reference#product-insights-POSTapi-v1-products--product_slug--insights-nps
     *
     * @param string $productSlug The slug of the product.
     * @param array  $data        The data to send to the API.
     *
     * @return \GroundLevel\Mothership\Api\Response The response from the API.
     */
    public static function nps(string $productSlug, array $data = []) : Response
    {
        return Request::post(self::getEndpoint($productSlug, 'nps'), $data);
    }
}
