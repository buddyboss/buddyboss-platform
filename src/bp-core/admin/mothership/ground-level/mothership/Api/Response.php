<?php

declare(strict_types=1);

namespace GroundLevel\Mothership\Api;

/**
 * API Response class for handling responses from the mothership API.
 */
class Response
{
    /**
     * Response data.
     *
     * @var mixed
     */
    public $data;

    /**
     * Error message.
     *
     * @var string
     */
    public $error;

    /**
     * HTTP status code.
     *
     * @var int
     */
    public $statusCode;

    /**
     * Error code.
     *
     * @var int
     */
    public $errorCode;

    /**
     * Products array for addon responses.
     *
     * @var array
     */
    public $products;

    /**
     * Constructor.
     *
     * @param mixed  $data        Response data.
     * @param string $error       Error message.
     * @param int    $statusCode HTTP status code.
     */
    public function __construct($data = null, $error = null, int $statusCode = 200)
    {
        $this->data = $data;
        $this->error = $error;
        $this->statusCode = $statusCode;
        $this->errorCode = $statusCode;

        // Handle products response.
        if ($data && isset($data->products)) {
            $this->products = $data->products;
        }
    }

    /**
     * Check if response is an error.
     *
     * @return bool True if error, false otherwise.
     */
    public function isError(): bool
    {
        return !empty($this->error) || $this->statusCode >= 400;
    }

    /**
     * Get error message.
     *
     * @return string Error message.
     */
    public function getError(): string
    {
        return $this->error ?: '';
    }

    /**
     * Get error code.
     *
     * @return int Error code.
     */
    public function getErrorCode(): int
    {
        return $this->errorCode;
    }
}
