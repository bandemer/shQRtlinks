<?php 

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Link;
use App\Repository\LinkRepository;
use App\Form\LinkType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class LinksController extends AbstractController
{
    #[Route(path: "/links", name: "links")]
    #[IsGranted('ROLE_USER', message: 'You are not allowed to access this site!')]
    public function links(LinkRepository $links) : Response
    {
        return $this->render('sites/links/links.html.twig', ['links' => $links->findBy(['User' => $this->getUser()])]);
    }

    #[Route(path: "/links/changestatus", name: "links_changestatus")]
    #[IsGranted('ROLE_USER', message: 'You are not allowed to access this site!')]
    public function changeStatusOfLink(Request $request, EntityManagerInterface $em) : JsonResponse
    {
        $id = $request->get('linkid', 0);
        $to = $request->get('to', 0);
        $token = $request->get('token');

        $link = $em->getRepository(Link::class)->find($id);

        $code = 200;

        if ($this->isCsrfTokenValid('links-changestatus', $token) AND
            $link->getId() > 0
            AND $link->getUser() === $this->getUser()) {

            $link->setStatus($to);
            $em->persist($link);
            $em->flush();
        }

        $response = ['message' => 'OK'];
        return $this->json($response, $code,
            [
                'Access-Control-Allow-Origin' => '*',
                'X-Robots-Tag' => 'noindex, nofollow'
            ]
        );
    }

    #[Route(path: "/links/new", name: "links_new")]
    #[IsGranted('ROLE_USER', message: 'You are not allowed to access this site!')]
    public function newLink(Request $request, EntityManagerInterface $em) : Response
    {
        $form = $this->createForm(LinkType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() AND $form->isValid()) {

            $link = $form->getData();
            $link->setUser($this->getUser());
            $em->persist($link);
            $em->flush();

            $this->addFlash('notice', 'Your link was successfully created!');
            return $this->redirectToRoute('links');
        }

        return $this->render('sites/links/new.html.twig', ['form' => $form, 'baseurl' => 'https://bmrx.de/' ]);
    }

    #[Route(path: "/links/edit", name: "links_edit")]
    #[IsGranted('ROLE_USER', message: 'You are not allowed to access this site!')]
    public function editLink(Request $request, EntityManagerInterface $em) : Response
    {
        $id = $request->get('linkid', 0);
        $link = $em->getRepository(Link::class)->find($id);

        if ($link->getUser() !== $this->getUser()) {
            $this->addFlash('error', 'You are not allowed to access this site!');
            return $this->redirectToRoute('links');
        }

        $form = $this->createForm(LinkType::class, $link);
        $form->handleRequest($request);

        if ($form->isSubmitted() AND $form->isValid()) {

            $link = $form->getData();
            $link->setUser($this->getUser());
            $em->persist($link);
            $em->flush();

            $this->addFlash('notice', 'The link was successfully updated!');
            return $this->redirectToRoute('links');
        }

        $token = $request->request->get('token');
        if (!$this->isCsrfTokenValid('links-edit', $token)) {
            $this->addFlash('error', 'Sorry, that didn\'t work. Please try again!');
            return $this->redirectToRoute('links');
        }

        return $this->render('sites/links/edit.html.twig',
            [
                'id' => $id,
                'form' => $form,
                'baseurl' => 'https://bmrx.de/'
            ]);
    }

    #[Route(path: "/links/delete", name: "links_delete")]
    #[IsGranted('ROLE_USER', message: 'You are not allowed to access this site!')]
    public function deleteLink(Request $request, EntityManagerInterface $em) : Response
    {
        $id = $request->get('linkid', 0);
        $link = $em->getRepository(Link::class)->find($id);
        $token = $request->request->get('token');

        if ($this->isCsrfTokenValid('links-delete', $token) AND
            $link->getId() > 0 AND
            $link->getUser() === $this->getUser()) {

            $em->remove($link);
            $em->flush();
            $this->addFlash('notice', 'Your link was successfully deleted!');
        } else {
            $this->addFlash('error', 'Deletion of link was not possible!');
        }

        return $this->redirectToRoute('links');
    }

    #[Route(path: "/{alias}", priority: -100, name: "shortlink")]
    public function goLink(EntityManagerInterface $em, string $alias) : RedirectResponse
    {
        $l = $em->getRepository(Link::class)->findOneBy(['alias' => $alias, 'status' => 1]);
        
        if (!$l) {
            throw $this->createNotFoundException('No link found for alias '.$alias);
        }
        
        $l->setClicks($l->getClicks() +1);
        $em->flush();
        
        $r = new RedirectResponse($l->getUrl());
        $r->setStatusCode(301);
        return $r;
    }
    
}