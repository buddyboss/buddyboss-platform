<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Support;

/**
 * Result.
 *
 * Represents the outcome of an operation with success status and optional message/data.
 */
class Result
{
    /**
     * Whether the operation succeeded.
     *
     * @var boolean
     */
    private bool $success;
    /**
     * Optional message describing the result.
     *
     * @var string
     */
    private string $message;
    /**
     * Optional data returned from the operation.
     *
     * @var mixed
     */
    private $data;
    /**
     * Constructs a new Result.
     *
     * @param boolean $success Whether the operation succeeded.
     * @param string  $message Optional message describing the result.
     * @param mixed   $data    Optional data returned from the operation.
     */
    public function __construct(bool $success, string $message = '', $data = null)
    {
        $this->success = $success;
        $this->message = $message;
        $this->data = $data;
    }
    /**
     * Creates a successful result.
     *
     * @param  string $message Optional success message.
     * @param  mixed  $data    Optional data to include.
     * @return self
     */
    public static function success(string $message = '', $data = null) : self
    {
        return new self(\true, $message, $data);
    }
    /**
     * Creates a failed result.
     *
     * @param  string $message Error message.
     * @param  mixed  $data    Optional error details.
     * @return self
     */
    public static function failure(string $message, $data = null) : self
    {
        return new self(\false, $message, $data);
    }
    /**
     * Checks if the operation succeeded.
     *
     * @return boolean
     */
    public function isSuccess() : bool
    {
        return $this->success;
    }
    /**
     * Checks if the operation failed.
     *
     * @return boolean
     */
    public function isFailure() : bool
    {
        return !$this->success;
    }
    /**
     * Gets the result message.
     *
     * @return string
     */
    public function getMessage() : string
    {
        return $this->message;
    }
    /**
     * Gets the result data.
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }
}
