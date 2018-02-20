<?php

namespace App\Api;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class ApiProblemException
 * @author yourname
 */
class ApiProblemException extends HttpException
{
    /**
     * The problem associated with the exception
     *
     * @var ApiProblem
     */
    protected $apiProblem;

    /**
     * ApiProblemException ctor
     */
    public function __construct(ApiProblem $apiProblem, \Exception $previous = null, array $headers = array(), ?int $code = 0)
    {
        $this->apiProblem = $apiProblem;

        parent::__construct(
            $apiProblem->getStatusCode(),
            $apiProblem->getTitle(),
            $previous,
            $headers,
            $code
        );
    }

    /**
     * Get the api problem associated to the exception
     *
     * @return ApiProblem
     */
    public function getApiProblem()
    {
        return $this->apiProblem;
    }
}
