<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Mothership\Api;

use BuddyBossPlatform\GroundLevel\Mothership\AbstractPluginConnection;
use BuddyBossPlatform\GroundLevel\Mothership\Credentials;
use BuddyBossPlatform\GroundLevel\Mothership\Util;
/**
 * Request class for the API. This returns the Response object.
 */
class Request
{
    /**
     * The cache key ID for the API cache.
     *
     * @var string
     */
    public const API_CACHE_ID = 'API_CACHE';
    /**
     * The plugin connection.
     *
     * @var AbstractPluginConnection
     */
    private AbstractPluginConnection $plugin;
    /**
     * The credentials instance.
     *
     * @var Credentials
     */
    private Credentials $credentials;
    /**
     * The utility instance.
     *
     * @var \GroundLevel\Mothership\Util
     */
    private Util $util;
    /**
     * The cache TTL in seconds.
     *
     * @inject \GroundLevel\Mothership\MothershipServiceProvider::PARAM_CACHE_TTL
     * @var    integer
     */
    private int $cacheTtl;
    /**
     * Extra headers applied to outgoing requests. Populated by {@see self::withHeader()}.
     *
     * @var array<string, string>
     */
    private array $extraHeaders = [];
    /**
     * Whether to return responses from the cache, if available.
     *
     * If `true`, the response will be retrieved from the cache, if available, otherwise a new request will be made.
     *
     * @var boolean
     */
    private bool $skipCache = \false;
    /**
     * Constructor.
     *
     * @param AbstractPluginConnection $plugin      The plugin connection.
     * @param Credentials              $credentials The credentials instance.
     * @param Util                     $util        The utility instance.
     * @param integer                  $cacheTtl    The cache TTL in seconds.
     */
    public function __construct(AbstractPluginConnection $plugin, Credentials $credentials, Util $util, int $cacheTtl = 60)
    {
        $this->plugin = $plugin;
        $this->credentials = $credentials;
        $this->util = $util;
        $this->cacheTtl = $cacheTtl;
    }
    /**
     * Perform a GET request.
     *
     * @param  string $endpoint The API endpoint to request.
     * @param  array  $params   Additional query parameters.
     * @return Response
     */
    public function get(string $endpoint, array $params = []) : Response
    {
        if (!empty($params)) {
            $endpoint = add_query_arg($params, $endpoint);
        }
        return $this->makeCachedGetRequest($endpoint);
    }
    /**
     * Perform a POST request.
     *
     * @param  string $endpoint The API endpoint to request.
     * @param  array  $body     The body of the request.
     * @param  array  $params   Additional query parameters.
     * @return Response
     */
    public function post(string $endpoint, array $body = [], array $params = []) : Response
    {
        return $this->makeRequest('POST', $endpoint, $body, $params);
    }
    /**
     * Perform a PATCH request.
     *
     * @param  string $endpoint The API endpoint to request.
     * @param  array  $body     The body of the request.
     * @param  array  $params   Additional query parameters.
     * @return Response
     */
    public function patch(string $endpoint, array $body = [], array $params = []) : Response
    {
        return $this->makeRequest('PATCH', $endpoint, $body, $params);
    }
    /**
     * Perform a PUT request.
     *
     * @param  string $endpoint The API endpoint to request.
     * @param  array  $body     The body of the request.
     * @param  array  $params   Additional query parameters.
     * @return Response
     */
    public function put(string $endpoint, array $body = [], array $params = []) : Response
    {
        return $this->makeRequest('PUT', $endpoint, $body, $params);
    }
    /**
     * Perform a DELETE request.
     *
     * @param  string $endpoint The API endpoint to request.
     * @param  array  $body     The body of the request.
     * @param  array  $params   Additional query parameters.
     * @return Response
     */
    public function delete(string $endpoint, array $body = [], array $params = []) : Response
    {
        return $this->makeRequest('DELETE', $endpoint, $body, $params);
    }
    /**
     * Returns a copy of this Request with caching disabled for the next request.
     *
     * This is useful for making a one-off request that should be retrieved from the API, bypassing the cache whether
     * or not it is available.
     *
     * This does NOT affect the cache TTL for the next request so the response will still be cached when the instance
     * TTL is greater than 0. To retrieve from the API and skip cache storage, chain {@see self::noStore()} to this
     * method.
     *
     * The original instance is not mutated.
     *
     * ```
     * $request->fresh()->get('/endpoint');
     * ```
     *
     * @return self A cloned Request with caching disabled.
     */
    public function fresh() : self
    {
        $clone = clone $this;
        $clone->skipCache = \true;
        return $clone;
    }
    /**
     * Returns a copy of this Request with the cache TTL set to 0.
     *
     * The original instance is not mutated. Useful for one-off, per-call requests that should not be stored in the cache.
     *
     * ```
     * $request->noStore()->get('/endpoint');
     * ```
     *
     * @return self
     */
    public function noStore() : self
    {
        return $this->withCacheTtl(0);
    }
    /**
     * Returns a copy of this Request with the given header applied to subsequent calls.
     *
     * The original instance is not mutated. Useful for one-off, per-call headers:
     *
     * ```
     * $request->withHeader('X-Foo', 'bar')->post('/endpoint', $body);
     * ```
     *
     * @param  string $name  The header name.
     * @param  string $value The header value.
     * @return self   A cloned Request carrying the header.
     */
    public function withHeader(string $name, string $value) : self
    {
        $clone = clone $this;
        $clone->extraHeaders[$name] = $value;
        return $clone;
    }
    /**
     * Returns a copy of this Request with the cache TTL set to the given value.
     *
     * By default, all GET requests are cached for {@see self::PARAM_CACHE_TTL}, this method is useful when you wish
     * to modify the cache TTL for a single one-off request or when caching should be disabled for a single request.
     *
     * To skip storing a response in the cache, pass `0` as the TTL or use the convenience method {@see self::noStore()}.
     *
     * The original instance is NOT mutated.
     *
     * Only GET requests are affected by the cache. Chaining this method before any non-GET request will have no effect.
     *
     * ```
     * $request->withCacheTtl(600)->get('/endpoint');
     * ```
     *
     * @param  integer $ttl The cache TTL in seconds.
     * @return self
     */
    public function withCacheTtl(int $ttl) : self
    {
        $clone = clone $this;
        $clone->cacheTtl = $ttl;
        return $clone;
    }
    /**
     * Returns a copy of this Request with the `X-Proxy-License-Key` header set.
     *
     * Used in the Email/Token auth strategy to attribute a request to a specific user's
     * license.
     *
     * @param  string $licenseKey The user's license key to proxy as.
     * @return self   A cloned Request carrying the header.
     */
    public function withProxyLicense(string $licenseKey) : self
    {
        return $this->withHeader('X-Proxy-License-Key', $licenseKey);
    }
    /**
     * Make an HTTP request.
     *
     * @param  string $method   The HTTP method to use.
     * @param  string $endpoint The API endpoint to request.
     * @param  array  $body     The body of the request.
     * @param  array  $params   Additional query parameters.
     * @return Response
     */
    private function makeRequest(string $method, string $endpoint, array $body = [], array $params = []) : Response
    {
        if (!empty($params)) {
            $endpoint = add_query_arg($params, $endpoint);
        }
        $url = $this->util->getApiBaseUrl() . \ltrim($endpoint, '/');
        $args = ['method' => $method, 'headers' => \array_merge($this->getAuthHeaders(), $this->extraHeaders)];
        if (!empty($body)) {
            $args['body'] = wp_json_encode($body);
            $args['data_format'] = 'body';
            $args['headers']['Content-Type'] = 'application/json; charset=utf-8';
            $args['headers']['Accept'] = 'application/json';
        }
        $response = wp_remote_request($url, $args);
        return $this->handleResponse($response);
    }
    /**
     * Make a cached GET request.
     *
     * @param  string $endpoint The API endpoint to request.
     * @return Response
     */
    private function makeCachedGetRequest(string $endpoint) : Response
    {
        $storeInCache = $this->cacheTtl > 0;
        $returnFromCache = \false === $this->skipCache;
        $cacheKey = $storeInCache || $returnFromCache ? $this->buildCacheKey($endpoint) : null;
        if ($returnFromCache) {
            $cachedResponse = get_transient($cacheKey);
            if ($cachedResponse instanceof Response) {
                return $cachedResponse;
            }
        }
        $response = $this->makeRequest('GET', $endpoint);
        if ($storeInCache && !$response->isError()) {
            set_transient($cacheKey, $response, $this->cacheTtl);
        }
        return $response;
    }
    /**
     * Build the transient cache key for a GET request.
     *
     * The hash covers the full outgoing request URL and headers (auth + per-call
     * extras) so responses fetched under one auth context cannot be served to
     * callers in another.
     *
     * @param  string $endpoint The API endpoint being requested.
     * @return string
     */
    private function buildCacheKey(string $endpoint) : string
    {
        $url = $this->util->getApiBaseUrl() . \ltrim($endpoint, '/');
        $headers = \array_change_key_case(\array_merge($this->getAuthHeaders(), $this->extraHeaders), \CASE_LOWER);
        \ksort($headers);
        return \implode('_', [$this->plugin->pluginId, 'mothership', self::API_CACHE_ID, \md5($url . '|' . \serialize($headers))]);
    }
    /**
     * Get authentication headers.
     *
     * @return array The authentication headers.
     */
    protected function getAuthHeaders() : array
    {
        $headers = [];
        $licenseKey = $this->credentials->getLicenseKey();
        $activationDomain = \rawurlencode($this->credentials->getDomain());
        if ($licenseKey && $activationDomain) {
            return ['Authorization' => 'Basic ' . \base64_encode("{$activationDomain}:{$licenseKey}")];
        }
        $email = $this->credentials->getEmail();
        $apiToken = $this->credentials->getApiToken();
        if ($email && $apiToken) {
            $headers['Authorization'] = 'Basic ' . \base64_encode("{$email}:{$apiToken}");
        }
        return $headers;
    }
    /**
     * Handle the API response.
     *
     * @param  array|\WP_Error $response The response from the API.
     * @return Response
     */
    protected function handleResponse($response) : Response
    {
        if (is_wp_error($response)) {
            return $this->handleWpError($response);
        }
        $body = wp_remote_retrieve_body($response);
        $data = \json_decode($body);
        $responseCode = (int) wp_remote_retrieve_response_code($response);
        return new Response($data, $responseCode, $this);
    }
    /**
     * Handle a WP_Error.
     *
     * @param  \WP_Error $response The response from the API.
     * @return Response
     */
    protected function handleWpError($response) : Response
    {
        $errorMessage = 'WP_Error : ';
        if (isset($response->errors)) {
            $index = 0;
            foreach ($response->errors as $key => $error) {
                $errorMessage .= \sprintf('%d. %s ', $index + 1, \implode(', ', $error));
                ++$index;
            }
        }
        return new Response((object) ['message' => $errorMessage], 500, $this);
    }
}
