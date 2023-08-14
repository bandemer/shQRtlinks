<?php 

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Entity\Links;
use App\Repository\LinksRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DefaultController extends AbstractController
{
    #[Route(path: "/", name: "index")]
    public function index(LinksRepository $links) : Response
    {
        return $this->render('sites/index.html.twig', ['links' => $links->findAll()]);
    }    
    
    #[Route(path: "/links", name: "links")]
    #[IsGranted('ROLE_USER', message: 'You are not allowed to access the admin dashboard.')]
    public function links(LinksRepository $links) : Response
    {
        return $this->render('sites/links.html.twig', ['links' => $links->findBy(['User' => $this->getUser()])]);
    }    
    
    #[Route(path: "/{alias}", priority: -100, name: "shortlink")]
    public function shortlink(EntityManagerInterface $em, string $alias) : RedirectResponse
    {
        $l = $em->getRepository(Links::class)->findOneBy(['alias' => $alias, 'status' => 1]);
        
        if (!$l) {
            throw $this->createNotFoundException('No link found for alias '.$alias);
        }
        
        $l->setClicks($l->getClicks() +1);
        $em->flush();
        
        $r = new RedirectResponse($l->getUrl());
        return $r;
    }
    
}