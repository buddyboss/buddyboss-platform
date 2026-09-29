<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Mothership\Api\Request;

use BuddyBossPlatform\GroundLevel\Mothership\Api\Response;
/**
 * This class is used to interact with the product insights API.
 *
 * @link https://licenses.caseproof.com/help/api-reference#product-insights
 */
class ProductInsights extends AbstractResource
{
    /**
     * Get the product insights endpoint.
     *
     * @param string $productSlug The slug of the product.
     * @param string $endpoint    The endpoint to get the product insights for.
     *
     * @return string The product insights endpoint.
     */
    private function getEndpoint(string $productSlug, string $endpoint) : string
    {
        return "products/{$productSlug}/insights/{$endpoint}";
    }
    /**
     * Send demographics data to the API.
     *
     * @link https://licenses.caseproof.com/help/api-reference#product-insights-POSTapi-v1-products--product_slug--insights-demographics
     *
     * @param string $productSlug The slug of the product.
     * @param array  $body        The body of the request.
     * @param array  $params      Additional query parameters.
     *
     * @return \GroundLevel\Mothership\Api\Response The response from the API.
     */
    public function demographics(string $productSlug, array $body = [], array $params = []) : Response
    {
        return $this->request->post($this->getEndpoint($productSlug, 'demographics'), $body, $params);
    }
    /**
     * Send NPS data to the API.
     *
     * @link https://licenses.caseproof.com/help/api-reference#product-insights-POSTapi-v1-products--product_slug--insights-nps
     *
     * @param string $productSlug The slug of the product.
     * @param array  $body        The body of the request.
     * @param array  $params      Additional query parameters.
     *
     * @return \GroundLevel\Mothership\Api\Response The response from the API.
     */
    public function nps(string $productSlug, array $body = [], array $params = []) : Response
    {
        return $this->request->post($this->getEndpoint($productSlug, 'nps'), $body, $params);
    }
}
