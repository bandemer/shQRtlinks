<?php 

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Entity\Link;
use App\Repository\LinkRepository;
use App\Form\LinkType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DefaultController extends AbstractController
{
    #[Route(path: "/", name: "index")]
    public function index(LinkRepository $links) : Response
    {
        return $this->render('sites/index.html.twig', ['links' => $links->findAll()]);
    }    
    
    #[Route(path: "/links", name: "links")]
    #[IsGranted('ROLE_USER', message: 'You are not allowed to access this site!')]
    public function links(LinkRepository $links) : Response
    {
        return $this->render('sites/links.html.twig', ['links' => $links->findBy(['User' => $this->getUser()])]);
    }

    #[Route(path: "/newlink", name: "newlink")]
    #[IsGranted('ROLE_USER', message: 'You are not allowed to access this site!')]
    public function newLink(Request $request, EntityManagerInterface $em) : Response
    {

        $link = new Link();
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

        return $this->render('sites/newlink.html.twig', ['form' => $form]);
    }
    
    #[Route(path: "/{alias}", priority: -100, name: "shortlink")]
    public function shortlink(EntityManagerInterface $em, string $alias) : RedirectResponse
    {
        $l = $em->getRepository(Link::class)->findOneBy(['alias' => $alias, 'status' => 1]);
        
        if (!$l) {
            throw $this->createNotFoundException('No link found for alias '.$alias);
        }
        
        $l->setClicks($l->getClicks() +1);
        $em->flush();
        
        $r = new RedirectResponse($l->getUrl());
        return $r;
    }
    
}