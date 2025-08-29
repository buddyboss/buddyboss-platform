<?php

declare(strict_types=1);

namespace GroundLevel\Mothership\Api;

use GroundLevel\Container\Concerns\HasStaticContainer;
use GroundLevel\Container\Contracts\StaticContainerAwareness;
use GroundLevel\Mothership\Service as MothershipService;

/**
 * Base API Request class for handling HTTP requests to the mothership API.
 */
class Request implements StaticContainerAwareness
{
    use HasStaticContainer;

    /**
     * The API base URL.
     *
     * @var string
     */
    protected string $apiBaseUrl;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->apiBaseUrl = $this->getApiBaseUrl();
    }

    /**
     * Get the API base URL.
     *
     * @return string The API base URL.
     */
    protected function getApiBaseUrl(): string
    {
        $mothershipService = self::getContainer()->get(MothershipService::class);
        return $mothershipService->getApiBaseUrl();
    }

    /**
     * Perform a GET request.
     *
     * @param string $endpoint The API endpoint.
     * @param array  $params   Query parameters.
     * @return Response The response object.
     */
    public function get(string $endpoint, array $params = []): Response
    {
        if (!empty($params)) {
            $endpoint = add_query_arg($params, $endpoint);
        }
        return $this->makeRequest('GET', $endpoint);
    }

    /**
     * Perform a POST request.
     *
     * @param string $endpoint The API endpoint.
     * @param array  $body     Request body.
     * @return Response The response object.
     */
    public function post(string $endpoint, array $body = []): Response
    {
        return $this->makeRequest('POST', $endpoint, $body);
    }

    /**
     * Perform a PATCH request.
     *
     * @param string $endpoint The API endpoint.
     * @param array  $body     Request body.
     * @return Response The response object.
     */
    public function patch(string $endpoint, array $body = []): Response
    {
        return $this->makeRequest('PATCH', $endpoint, $body);
    }

    /**
     * Perform a PUT request.
     *
     * @param string $endpoint The API endpoint.
     * @param array  $body     Request body.
     * @return Response The response object.
     */
    public function put(string $endpoint, array $body = []): Response
    {
        return $this->makeRequest('PUT', $endpoint, $body);
    }

    /**
     * Perform a DELETE request.
     *
     * @param string $endpoint The API endpoint.
     * @return Response The response object.
     */
    public function delete(string $endpoint): Response
    {
        return $this->makeRequest('DELETE', $endpoint);
    }

    /**
     * Make an HTTP request.
     *
     * @param string $method  The HTTP method.
     * @param string $endpoint The API endpoint.
     * @param array  $body    Request body.
     * @return Response The response object.
     */
    protected function makeRequest(string $method, string $endpoint, array $body = []): Response
    {
        $url = $this->apiBaseUrl . ltrim($endpoint, '/');
        
        $args = [
            'method'  => $method,
            'timeout' => 30,
            'headers' => $this->getHeaders(),
        ];

        if (!empty($body)) {
            $args['body'] = json_encode($body);
            $args['headers']['Content-Type'] = 'application/json';
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return new Response(null, $response->get_error_message(), 500);
        }

        $responseCode = wp_remote_retrieve_response_code($response);
        $responseBody = wp_remote_retrieve_body($response);
        $data = json_decode($responseBody);

        if ($responseCode >= 200 && $responseCode <= 299) {
            return new Response($data, null, $responseCode);
        }

        // Handle error response.
        $errorMessage = isset($data->message) ? $data->message : 'Unknown error';
        
        if (isset($data->errors) && is_object($data->errors)) {
            $errors = [];
            foreach ($data->errors as $field => $messages) {
                $errors[] = $field . ': ' . implode(', ', $messages);
            }
            if (!empty($errors)) {
                $errorMessage .= ' - ' . implode('; ', $errors);
            }
        }

        return new Response(null, $errorMessage, $responseCode);
    }

    /**
     * Get request headers.
     *
     * @return array The headers array.
     */
    protected function getHeaders(): array
    {
        $headers = [
            'Accept' => 'application/json',
            'User-Agent' => 'BuddyBoss-Platform/1.0',
        ];

        // Add authentication headers if needed.
        $licenseKey = \GroundLevel\Mothership\Credentials::getLicenseKey();
        $activationDomain = \GroundLevel\Mothership\Credentials::getActivationDomain();
        
        if ($licenseKey && $activationDomain) {
            $headers['Authorization'] = 'Basic ' . base64_encode("$activationDomain:$licenseKey");
        }

        return $headers;
    }
}
