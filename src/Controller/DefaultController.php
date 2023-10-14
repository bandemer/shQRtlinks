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
}