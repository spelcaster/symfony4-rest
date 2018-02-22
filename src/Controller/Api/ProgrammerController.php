<?php

namespace App\Controller\Api;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use App\Controller\BaseController;
use App\Entity\Programmer;
use App\Form\Type\ProgrammerType;
use App\Form\Type\UpdateProgrammerType;
use App\Pagination\PaginationFactory;

/**
 * @Security("is_granted('ROLE_USER')")
 */
class ProgrammerController extends BaseController
{
    /**
     * Create paginated responses
     *
     * @var PaginationFactory
     */
    protected $paginationFactory;

    /**
     * ProgrammerController ctor
     *
     * @param mixed PaginationFactory $paginationFactory
     */
    public function __construct(PaginationFactory $paginationFactory)
    {
        $this->paginationFactory = $paginationFactory;
    }

    /**
     * @Route("/api/programmers", name="api_programmer_new")
     * @Method("POST")
     */
    public function new(Request $request)
    {
        $programmer = new Programmer();
        $form = $this->createForm(ProgrammerType::class, $programmer);
        $this->processForm($request, $form);

        if (!$form->isValid()) {
            throw $this->createApiProblemValidationException($form);
        }

        $programmer->setUser($this->findUserByUsername('user'));

        $em = $this->getDoctrine()->getManager();
        $em->persist($programmer);
        $em->flush();

        $location = $this->generateUrl('api_programmer_show', [
            'nickname' => $programmer->getNickname()
        ]);

        $response = $this->createApiResponse($programmer, 201);
        $response->headers->set('Location', $location);

        return $response;
    }

    /**
     * @Route("/api/programmers/{nickname}", name="api_programmer_show")
     * @Method("GET")
     */
    public function show($nickname)
    {
        $programmer = $this->getDoctrine()
            ->getRepository('App:Programmer')
            ->findOneByNickname($nickname);

        if (!$programmer) {
            throw $this->createNotFoundException(
                "No programmer found with username $nickname"
            );
        }

        return $this->createApiResponse($programmer);
    }

    /**
     * @Route("/api/programmers", name="api_programmer_collection")
     * @Method("GET")
     */
    public function list(Request $request)
    {
        $filter = $request->query->get('filter');

        $queryBuilder = $this->getDoctrine()
            ->getRepository('App:Programmer')
            ->findAllQueryBuilder($filter);

        $route = 'api_programmer_collection';
        $paginatedCollection = $this->paginationFactory->createCollection(
            $queryBuilder, $request, $route
        );

        return $this->createApiResponse($paginatedCollection);
    }

    /**
     * @Route("/api/programmers/{nickname}", name="api_programmer_update")
     * @Method({"PUT", "PATCH"})
     */
    public function update($nickname, Request $request)
    {
        $programmer = $this->getDoctrine()
            ->getRepository('App:Programmer')
            ->findOneByNickname($nickname);

        if (!$programmer) {
            throw $this->createNotFoundException(
                "No programmer found with username $nickname"
            );
        }

        $form = $this->createForm(UpdateProgrammerType::class, $programmer);
        $this->processForm($request, $form);

        if (!$form->isValid()) {
            throw $this->createApiProblemValidationException($form);
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($programmer);
        $em->flush();

        return $this->createApiResponse($programmer);
    }

    /**
     * @Route("/api/programmers/{nickname}", name="api_programmer_delete")
     * @Method("DELETE")
     */
    public function delete($nickname)
    {
        $programmer = $this->getDoctrine()
            ->getRepository('App:Programmer')
            ->findOneByNickname($nickname);

        if ($programmer) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($programmer);
            $em->flush();
        }

        return $this->createApiResponse([], 204);
    }
}
