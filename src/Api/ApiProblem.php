<?php

namespace App\Api;

use Symfony\Component\HttpFoundation\Response;

/**
 * A wrapper for holding data to be used for a 'application/problem+json'
 * response
 *
 * See https://tools.ietf.org/html/draft-ietf-appsawg-http-problem-03#section-4.1
 */
final class ApiProblem
{
    /**
     * Constant type for unknown errors
     */
    const TYPE_UNKNOWN_ERROR = 'unknown';

    /**
     * Constant type for unknown http status error
     */
    const TYPE_HTTP_UNKNOWN_STATUS_ERROR = 'status_unknown';

    /**
     * Constant type for form validation errors
     */
    const TYPE_VALIDATION_ERROR = 'validation_error';

    /**
     * Constant type for invalid request body
     */
    const TYPE_INVALID_REQUEST_BODY_FORMAT = 'invalid_body_format';

    /**
     * Titles mapped according to the error type
     */
    private static $titles = [
        self::TYPE_UNKNOWN_ERROR => 'There was an unknown error',
        self::TYPE_HTTP_UNKNOWN_STATUS_ERROR => 'There was an unknown http status error',
        self::TYPE_VALIDATION_ERROR => 'There was a validation errors',
        self::TYPE_INVALID_REQUEST_BODY_FORMAT => 'Invalid JSON format sent',
    ];

    /**
     * HTTP Status
     *
     * @var int
     */
    protected $statusCode;

    /**
     * Response type
     *
     * @var string
     */
    protected $type;

    /**
     * Response title
     *
     * @var string
     */
    protected $title;

    /**
     * Response extra data
     *
     * @var array
     */
    protected $extraData;

    /**
     * Constructor
     *
     * @param mixed $statusCode HTTP Status
     * @param mixed $type       Type
     * @param mixed $title      Title
     */
    public function __construct($statusCode, $type = null)
    {
        $this->statusCode = $statusCode;
        $this->extraData = [];

        if (!$type) {
            $this->type = 'about:blank';

            $this->title = isset(Response::$statusTexts[$statusCode])
                ? Response::$statusTexts[$statusCode]
                : self::$titles[self::TYPE_HTTP_UNKNOWN_STATUS_ERROR];

            return;
        }

        $this->type = $type;
        if (!isset(self::$titles[$type])) {
            throw new \InvalidArgumentException("No title for type $type");
        }
        $this->title = self::$titles[$type];
    }

    /**
     * Get data as array
     *
     * @return array
     */
    public function toArray()
    {
        return array_merge(
            $this->extraData,
            [
                'status' => $this->statusCode,
                'type' => $this->type,
                'title' => $this->title
            ]
        );
    }

    /**
     * Set an extra data
     *
     * @param string $name  Key to the associative array
     * @param mixed  $value A value to be stored in the extra data
     *
     * @return void
     */
    public function set($name, $value)
    {
        $this->extraData[$name] = $value;
    }

    /**
     * Get the status code
     *
     * @return void
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Get the problem title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;

    }
}
