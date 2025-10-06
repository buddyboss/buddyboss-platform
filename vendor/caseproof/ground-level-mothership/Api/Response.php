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
    protected $data;
    /**
     * The error message, if any.
     *
     * @var string
     */
    protected $error;
    /**
     * The error code, if any.
     *
     * @var integer
     */
    protected $errorCode;
    /**
     * The Details of the error, if any.
     *
     * @var array
     */
    protected $errors;
    /**
     * Constructor for the Response class.
     *
     * @param mixed $data      The data to be stored in the response.
     * @param mixed $error     The error Message, if any.
     * @param mixed $errorCode The error code, if any.
     * @param array $errors    The errors, if any.
     */
    public function __construct($data = null, $error = null, $errorCode = null, $errors = null)
    {
        $this->data = $data;
        $this->error = $error;
        $this->errorCode = $errorCode;
        $this->errors = $errors;
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
     * Magic getter to access response properties.
     * Returns the property if it exists, otherwise it returns the error.
     *
     * @param  string $name The name of the property to get.
     * @return mixed The value of the property or the error.
     */
    public function __get(string $name)
    {
        return $this->returnDataOrError($name);
    }
    /**
     * Magic isset to check if data or error exists.
     *
     * @param  string $name The name of the property to check.
     * @return boolean
     */
    public function __isset(string $name) : bool
    {
        if ($this->isError() && \in_array($name, ['error', 'errorCode', 'errors'], \true)) {
            return \true;
        }
        return isset($this->data->{$name});
    }
    /**
     * Check if the response has a link.
     *
     * @param  string $rel The rel of the link to check for.
     * @return boolean
     */
    public function hasLink(string $rel) : bool
    {
        return isset($this->data->_links->{$rel});
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
     * Check if the response is an error.
     *
     * @return boolean True if the response is an error, false otherwise.
     */
    public function isError() : bool
    {
        return !empty($this->error);
    }
    /**
     * Check if the response is successful.
     *
     * @return boolean True if the response is successful, false otherwise.
     */
    public function isSuccess() : bool
    {
        return empty($this->error);
    }
    /**
     * Perform a request to a link.
     *
     * @param  string $rel  The rel of the link to request.
     * @param  array  $args The arguments to pass to the request.
     * @return \GroundLevel\Mothership\Api\Response|null The response from the link request.
     */
    protected function performLinkRequest(string $rel, array $args = []) : ?Response
    {
        $link = $this->data->_links->{$rel};
        $endpoint = \basename(wp_parse_url($link->href, \PHP_URL_PATH));
        $method = \strtolower($link->method ?? 'GET');
        $qs = wp_parse_url($link->href, \PHP_URL_QUERY);
        if ($qs) {
            \parse_str($qs, $query);
            $args = \array_merge($query, $args);
        }
        return Request::getContainer()->get(RequestFactory::class)->{$method}($endpoint, $args);
    }
    /**
     * Return the data or the error.
     *
     * @param  string $name The name of the property to get.
     * @return mixed The value of the property or the error.
     */
    private function returnDataOrError(string $name)
    {
        if ($this->isError()) {
            if ($name === 'errorCode') {
                return $this->errorCode;
            }
            if ($name === 'errors') {
                return $this->errors;
            }
            return $this->error;
        }
        if (!isset($this->data->{$name})) {
            return \sprintf(
                // Translators: %s The name of the property that does not exist on the response object.
                esc_html__('Property %s does not exist on the response object.', 'caseproof-mothership'),
                $name
            );
        }
        return $this->data->{$name};
    }
    /**
     * Convert the response to an array.
     *
     * @return array The response as an array.
     */
    public function toArray() : array
    {
        if ($this->isError()) {
            return ['error' => $this->error, 'errorCode' => $this->errorCode, 'errors' => $this->errors];
        }
        return (array) $this->data;
    }
}
