<?php

namespace DataMapper\Tools\Exceptions;

use Throwable;

/**
 * Class BusinessException
 * @package DataMapper\Tools\Exceptions
 */
class BusinessException extends \Exception
{
    protected $errorCode = '';

    protected $details = [];

    /**
     * BusinessException constructor.
     * @param string $errorCode
     * @param string|null $message
     * @param array|null $details
     * @param Throwable|null $previous
     */
    public function __construct(string $errorCode, ?string $message = null, ?array $details = null, Throwable $previous = null)
    {
        $this->errorCode = $errorCode;
        $this->details = $details;

        parent::__construct($message ?: $errorCode, 400, $previous);
    }

    /**
     * @return string
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * @return array|null
     */
    public function getDetails()
    {
        return is_array($this->details) ? $this->details : [];
    }

    /**
     * @param $key
     * @return mixed|null
     */
    public function getDetail($key)
    {
        return $this->details[$key] ?? null;
    }
}
