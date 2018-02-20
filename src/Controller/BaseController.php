<?php

namespace App\Controller;

use Symfony\Component\Form\FormInterface;
use JMS\Serializer\SerializationContext;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\HttpFoundation\Request;

use App\Api\ApiProblem;
use App\Api\ApiProblemException;
use App\Entity\User;
use App\Battle\BattleManager;
use App\Repository\ProgrammerRepository;
use App\Repository\UserRepository;
use App\Repository\ProjectRepository;
use App\Repository\BattleRepository;
use App\Repository\ApiTokenRepository;

abstract class BaseController extends Controller
{
    protected $battleManager;

    /**
     * @param mixed BattleManager $battleManager
     */
    public function __construct(BattleManager $battleManager)
    {
        $this->battleManager = $battleManager;
    }


    /**
     * Is the current user logged in?
     *
     * @return boolean
     */
    public function isUserLoggedIn()
    {
        return $this->container->get('security.authorization_checker')
            ->isGranted('IS_AUTHENTICATED_FULLY');
    }

    /**
     * Logs this user into the system
     *
     * @param User $user
     */
    public function loginUser(User $user)
    {
        $token = new UsernamePasswordToken($user, $user->getPassword(), 'main', $user->getRoles());

        $this->container->get('security.token_storage')->setToken($token);
    }

    public function addFlash($message, $positiveNotice = true)
    {
        /** @var Request $request */
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $noticeKey = $positiveNotice ? 'notice_happy' : 'notice_sad';

        $request->getSession()->getFlashbag()->add($noticeKey, $message);
    }

    /**
     * Used to find the fixtures user - I use it to cheat in the beginning
     *
     * @param $username
     * @return User
     */
    public function findUserByUsername($username)
    {
        return $this->getUserRepository()->findUserByUsername($username);
    }

    /**
     * @return UserRepository
     */
    protected function getUserRepository()
    {
        return $this->getDoctrine()
            ->getRepository('App:User');
    }

    /**
     * @return ProgrammerRepository
     */
    protected function getProgrammerRepository()
    {
        return $this->getDoctrine()
            ->getRepository('App:Programmer');
    }

    /**
     * @return ProjectRepository
     */
    protected function getProjectRepository()
    {
        return $this->getDoctrine()
            ->getRepository('App:Project');
    }

    /**
     * @return BattleRepository
     */
    protected function getBattleRepository()
    {
        return $this->getDoctrine()
            ->getRepository('App:Battle');
    }

    /**
     * @return \App\Battle\BattleManager
     */
    protected function getBattleManager()
    {
        return $this->battleManager;
    }

    /**
     * @return ApiTokenRepository
     */
    protected function getApiTokenRepository()
    {
        return $this->getDoctrine()
            ->getRepository('App:ApiToken');
    }

    protected function createApiResponse($data, $statusCode = 200)
    {
        $json = $this->serialize($data);

        return new Response($json, $statusCode, array(
            'Content-Type' => 'application/json'
        ));
    }

    protected function processForm(Request $request, FormInterface $form)
    {
        $body = $request->getContent();
        $data = json_decode($body, true);

        if (JSON_ERROR_NONE != json_last_error()) {
            $apiProblem = new ApiProblem(
                422, ApiProblem::TYPE_INVALID_REQUEST_BODY_FORMAT
            );

            throw new ApiProblemException($apiProblem);
        }

        $clearMissing = $request->getMethod() != 'PATCH';
        $form->submit($data, $clearMissing);
    }

    protected function getErrorsFromForm(FormInterface $form)
    {
        $errors = [];

        foreach ($form->getErrors() as $error) {
            $errors[] = $error->getMessage();
        }

        foreach ($form->all() as $childForm) {
            if (!$childForm instanceof FormInterface) {
                continue;
            }

            if ($childErrors = $this->getErrorsFromForm($childForm)) {
                $errors[$childForm->getName()] = $childErrors;
            }
        }

        return $errors;
    }

    protected function createApiProblemValidationException(FormInterface $form)
    {
        $errors = $this->getErrorsFromForm($form);

        $apiProblem = new ApiProblem(400, ApiProblem::TYPE_VALIDATION_ERROR);
        $apiProblem->set('errors', $errors);

        return new ApiProblemException($apiProblem);
    }

    protected function serialize($data, $format = 'json')
    {
        $context = new SerializationContext();
        $context->setSerializeNull(true);

        $request = $this->get('request_stack')->getCurrentRequest();
        $groups = array('Default');
        if ($request->query->get('deep')) {
            $groups[] = 'deep';
        }
        $context->setGroups($groups);

        return $this->container->get('jms_serializer')
            ->serialize($data, $format, $context);
    }
}
