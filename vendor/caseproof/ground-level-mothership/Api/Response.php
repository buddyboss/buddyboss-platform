<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Mothership\Api;

use BuddyBossPlatform\GroundLevel\Support\Contracts\Arrayable;
/**
 * A response from the Mothership API.
 *
 * @method ?Response first(array $args = []) Retrieves the first page of data for the collection or `null` for non-paginated responses.
 * @method ?Response last(array $args = [])  Retrieves the last page of data for the collection or `null` for non-paginated responses.
 * @method ?Response next(array $args = [])  Retrieves the next page of data for the collection or `null` for non-paginated responses.
 * @method ?Response prev(array $args = [])  Retrieves the previous page of data for the collection or `null` for non-paginated responses.
 * @method ?Response self(array $args = [])  Retrieves the current page of data for the collection or `null` for non-paginated responses.
 *
 * @package GroundLevel\Mothership\Api
 */
class Response implements Arrayable
{
    /**
     * The data to be stored in the response.
     *
     * @var mixed
     */
    public $data;
    /**
     * The HTTP status code.
     *
     * @var integer
     */
    public int $statusCode;
    /**
     * The request instance for following pagination links.
     *
     * @var Request|null
     */
    protected ?Request $request = null;
    /**
     * Constructor for the Response class.
     *
     * @param mixed        $data       The response data.
     * @param integer      $statusCode The HTTP status code.
     * @param Request|null $request    The request instance.
     */
    public function __construct($data = null, int $statusCode = 200, ?Request $request = null)
    {
        $this->data = $data;
        $this->statusCode = $statusCode;
        $this->request = $request;
    }
    /**
     * Magic method to call a link request.
     *
     * Makes a request to a valid link by providing the relation (rel) name.
     *
     * @param  string $rel       The rel of the link to request.
     * @param  array  $arguments The arguments to pass to the request.
     * @return \GroundLevel\Mothership\Api\Response|null The response from the link request.
     * @throws \RuntimeException If the requested link does not exist.
     */
    public function __call(string $rel, array $arguments = []) : ?Response
    {
        if ($this->hasLink($rel)) {
            return $this->performLinkRequest($rel, ...$arguments);
        }
        throw new \RuntimeException(\sprintf('Method %s does not exist on the response object.', $rel));
    }
    /**
     * Returns the message field from the response data.
     *
     * The message field is typically used for error responses to provide a human-readable description of the error.
     *
     * @return string
     */
    public function getMessage() : string
    {
        return $this->getData('message', '');
    }
    /**
     * Returns the error code from the response data.
     *
     * @return string|null
     */
    public function getErrorCode() : ?string
    {
        return $this->getData('code');
    }
    /**
     * Returns the error type from the response data.
     *
     * @return string|null
     */
    public function getErrorType() : ?string
    {
        return $this->getData('type');
    }
    /**
     * Returns an array of errors from the response data.
     *
     * The API returns errors as an object, but we cast it to an array for easier handling.
     *
     * @return array<string, string[]> Key-value pairs of error codes and messages.
     */
    public function getErrors() : array
    {
        return (array) $this->getData('errors', []);
    }
    /**
     * Returns a combined error message from the main message and field-level errors.
     *
     * @return string
     */
    public function getErrorMessage() : string
    {
        $errors = $this->getErrors();
        $messages = empty($errors) ? [] : \array_merge(...\array_values($errors));
        $parts = \array_filter(\array_merge([$this->getMessage()], $messages));
        return \implode(' ', $parts);
    }
    /**
     * Returns an embedded resource by relation name.
     *
     * Supports dot notation for nested embeds (e.g. 'license.product').
     *
     * @param  string $rel The relation name of the embedded resource.
     * @return object|null The embedded resource object, or null if not found.
     */
    public function getEmbed(string $rel) : ?object
    {
        $embedded = $this->getData('_embedded');
        if (null === $embedded) {
            return null;
        }
        $segments = \explode('.', $rel);
        $current = $embedded->{\array_shift($segments)} ?? null;
        foreach ($segments as $segment) {
            if (!\is_object($current)) {
                return null;
            }
            $current = $current->_embedded->{$segment} ?? null;
        }
        return $current;
    }
    /**
     * Check if the response has a link.
     *
     * @param  string $rel The rel of the link to check for.
     * @return boolean
     */
    public function hasLink(string $rel) : bool
    {
        $links = $this->getData('_links');
        return $links && isset($links->{$rel});
    }
    /**
     * Check if there is a next page of data.
     *
     * @return boolean
     */
    public function hasNext() : bool
    {
        return $this->hasLink('next');
    }
    /**
     * Check if the response has pagination.
     *
     * @return boolean
     */
    public function hasPagination() : bool
    {
        return $this->hasNext() || $this->hasPrevious();
    }
    /**
     * Check if there is a previous page of data.
     *
     * @return boolean
     */
    public function hasPrevious() : bool
    {
        return $this->hasLink('prev');
    }
    /**
     * Returns a field from the response data.
     *
     * @param  string $field   The field to retrieve from the response data.
     * @param  mixed  $default The default value to return if the field or data is not available.
     * @return mixed
     */
    public function getData(string $field, $default = null)
    {
        if (null === $this->data) {
            return $default;
        }
        return $this->data->{$field} ?? $default;
    }
    /**
     * Check if the response is successful.
     *
     * @return boolean
     */
    public function isSuccess() : bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }
    /**
     * Check if the response is a redirect.
     *
     * @return boolean
     */
    public function isRedirect() : bool
    {
        return $this->statusCode >= 300 && $this->statusCode < 400;
    }
    /**
     * Check if the response is a client error.
     *
     * @return boolean
     */
    public function isClientError() : bool
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }
    /**
     * Check if the response is a server error.
     *
     * @return boolean
     */
    public function isServerError() : bool
    {
        return $this->statusCode >= 500 && $this->statusCode < 600;
    }
    /**
     * Check if the response is an error.
     *
     * @return boolean
     */
    public function isError() : bool
    {
        return $this->isClientError() || $this->isServerError();
    }
    /**
     * Check if the response is unauthorized (401).
     *
     * @return boolean
     */
    public function isUnauthorized() : bool
    {
        return 401 === $this->statusCode;
    }
    /**
     * Check if the response is forbidden (403).
     *
     * @return boolean
     */
    public function isForbidden() : bool
    {
        return 403 === $this->statusCode;
    }
    /**
     * Check if the response is not found (404).
     *
     * @return boolean
     */
    public function isNotFound() : bool
    {
        return 404 === $this->statusCode;
    }
    /**
     * Perform a request to a link.
     *
     * @param  string $rel  The rel of the link to request.
     * @param  array  $args The arguments to pass to the request.
     * @return \GroundLevel\Mothership\Api\Response|null The response from the link request.
     * @throws \RuntimeException If the request instance is not available.
     */
    protected function performLinkRequest(string $rel, array $args = []) : ?Response
    {
        if (null === $this->request) {
            throw new \RuntimeException('Cannot follow pagination links: Request instance not available.');
        }
        $links = $this->getData('_links');
        if (null === $links) {
            return null;
        }
        $link = $links->{$rel};
        $endpoint = \basename(wp_parse_url($link->href, \PHP_URL_PATH));
        $method = \strtolower($link->method ?? 'GET');
        $qs = wp_parse_url($link->href, \PHP_URL_QUERY);
        if ($qs) {
            \parse_str($qs, $query);
            $args = \array_merge($query, $args);
        }
        return $this->request->{$method}($endpoint, $args);
    }
    /**
     * Converts the response to an array.
     *
     * @return array The response as an array.
     */
    public function toArray() : array
    {
        if (null === $this->data) {
            return [];
        }
        return (array) $this->data;
    }
}
