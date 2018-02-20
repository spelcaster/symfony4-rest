<?php

namespace App\Controller\Api;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Controller\BaseController;
use App\Entity\Programmer;
use App\Form\Type\ProgrammerType;
use App\Form\Type\UpdateProgrammerType;

class ProgrammerController extends BaseController
{
    /**
     * @Route("/api/programmers", name="api_programmer_new")
     * @Method("POST")
     */
    public function new(Request $request)
    {
        $programmer = new Programmer();
        $form = $this->createForm(ProgrammerType::class, $programmer);
        $this->processForm($request, $form);

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
     * @Route("/api/programmers", name="api_programmer_list")
     * @Method("GET")
     */
    public function list()
    {
        $programmers = $this->getDoctrine()
            ->getRepository('App:Programmer')
            ->findAll();

        $data = ['programmers' => $programmers];

        return $this->createApiResponse($data);
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

    private function processForm(Request $request, FormInterface $form)
    {
        $body = $request->getContent();
        $data = json_decode($body, true);

        $clearMissing = $request->getMethod() != 'PATCH';

        $form->submit($data, $clearMissing);
    }
}
