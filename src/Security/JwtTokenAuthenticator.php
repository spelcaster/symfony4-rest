<?php

namespace App\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Authentication\Token\JWTUserToken;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Authentication\Token\PreAuthenticationJWTUserToken;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTAuthenticatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\TokenExtractorInterface;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\AuthorizationHeaderTokenExtractor;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\ExpiredTokenException;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\InvalidTokenException;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use App\Api\ApiProblem;
use App\Api\ResponseFactory;

/**
 * Class JwtTokenAuthenticator
 *
 * See https://symfony.com/doc/current/security/guard_authentication.html
 */
class JwtTokenAuthenticator extends AbstractGuardAuthenticator
{
    /**
     * @var JWTEncoderInterface
     */
    protected $jwtEncoder;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var TokenExtractorInterface
     */
    protected $tokenExtractor;

    /**
     * @var ResponseFactory
     */
    protected $responseFactory;

    /**
     * JwtTokenAuthenticator ctor
     */
    public function __construct(
        JWTEncoderInterface $jwtEncoder,
        EventDispatcherInterface $dispatcher,
        ResponseFactory $responseFactory
    ) {
        $this->jwtEncoder = $jwtEncoder;
        $this->dispatcher = $dispatcher;
        $this->responseFactory = $responseFactory;

        $this->tokenExtractor = new AuthorizationHeaderTokenExtractor(
            'Bearer', 'Authorization'
        );
        $this->tokenStorage = new TokenStorage();
    }

    /**
     * Called when authentication is needed, but it's not sent
     */
    public function start(
        Request $request, AuthenticationException $authException = null
    ) {
        $apiProblem = new ApiProblem(Response::HTTP_UNAUTHORIZED);
        $message = $authException
            ? $authException->getMessageKey() : 'Missing credential.';
        $apiProblem->set('detail', $message);

        return $this->responseFactory->createResponse($apiProblem);
    }

    /**
     * Called on every request to decide if this authenticator should be
     * used for the request. Returning false will cause this authenticator
     * to be skipped.
     */
    public function supports(Request $request)
    {
        return (false !== $this->tokenExtractor->extract($request));
    }

    /**
     * Called on every request. Return whatever credentials you want to
     * be passed to getUser() as $credentials.
     */
    public function getCredentials(Request $request)
    {

        $token = $this->tokenExtractor->extract($request);

        if (!$token) {
            return;
        }

        $preAuthToken = new PreAuthenticationJWTUserToken($token);

        try {
            $payload = $this->jwtEncoder->decode($token);
            if (!$payload) {
                throw new InvalidTokenException('Invalid JWT Token');
            }

            $preAuthToken->setPayload($payload);
        } catch (JWTDecodeFailureException $e) {
            if (
                JWTDecodeFailureException::EXPIRED_TOKEN === $e->getReason()
            ) {
                throw new ExpiredTokenException();
            }

            throw new InvalidTokenException('Invalid JWT Token', 0, $e);
        }

        return $preAuthToken;
    }


    /**
     * {@inheritdoc}
     */
    public function getUser(
        $preAuthToken, UserProviderInterface $userProvider
    ) {
        if (!$preAuthToken instanceof PreAuthenticationJWTUserToken) {
            throw new \InvalidArgumentException(
                sprintf('The first argument of the "%s()" method must be an instance of "%s".', __METHOD__, PreAuthenticationJWTUserToken::class)
            );
        }

        $payload = $preAuthToken->getPayload();
        $username = $payload['username'];

        $user = $userProvider->loadUserByUsername($username);

        if (!$user) {
            throw \Exception('User not found');
        }

        $this->tokenStorage->setToken($preAuthToken);

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \RuntimeException If there is no pre-authenticated token previously stored
     */
    public function createAuthenticatedToken(UserInterface $user, $providerKey)
    {
        $preAuthToken = $this->tokenStorage->getToken();

        if (null === $preAuthToken) {
            throw new \RuntimeException('Unable to return an authenticated token since there is no pre authentication token.');
        }

        $authToken = new JWTUserToken($user->getRoles(), $user, $preAuthToken->getCredentials(), $providerKey);

        $this->dispatcher->dispatch(Events::JWT_AUTHENTICATED, new JWTAuthenticatedEvent($preAuthToken->getPayload(), $authToken));
        $this->tokenStorage->setToken(null);

        return $authToken;
    }

    /**
     * {@inheritdoc}
     */
    public function onAuthenticationFailure(
        Request $request, AuthenticationException $exception
    ) {
        $apiProblem = new ApiProblem(Response::HTTP_UNAUTHORIZED);
        $apiProblem->set('detail', $exception->getMessageKey());

        return $this->responseFactory->createResponse($apiProblem);
    }

    /**
     * {@inheritdoc}
     */
    public function onAuthenticationSuccess(
        Request $request, TokenInterface $token, $providerKey
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function supportsRememberMe()
    {
        return false;
    }
}
