<?php 

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends AbstractController
{
    #[Route(path: "/", name: "index")]
    public function index() : Response
    {
        return $this->render('sites/index.html.twig');
    }


    #[Route(path: "/contact", name: "contact")]
    public function contac() : Response
    {
        return $this->render('sites/contact.html.twig');
    }

    #[Route(path: "/imprint", name: "imprint")]
    public function imprint() : Response
    {
        return $this->render('sites/imprint.html.twig');
    }

    #[Route(path: "/privacy", name: "privacy")]
    public function privacy() : Response
    {
        return $this->render('sites/privacy.html.twig');
    }
}