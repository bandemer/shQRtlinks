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
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class LinksController extends AbstractController
{
    private $linksPerPage = 10;

    private $linksPerPageChoices = [5, 10, 25, 50, 100];

    private $currentPage = 0;

    private $orderBy = '';

    private $orderByColumns = ['id', 'clicks', 'alias'];

    private $orderDir = '';

    private $searchQuery = '';

    private $filter = [];

    #[Route(path: "/links", name: "links")]
    #[IsGranted('ROLE_USER', message: 'You are not allowed to access this site!')]
    public function links(LinkRepository $links, SessionInterface $session, Request $req) : Response
    {
        $data = [
            'links'                 => [],
            'page'                  => 0,
            'linksperpage'          => 0,
            'linksperpagechoices'   => $this->linksPerPageChoices,
            'amount'                => 0,
            'searchquery'           => '',
            'pagination'            => [],
            'filter'                => [],
        ];

        $filter = $this->getFilter($session);

        $data['linksperpage'] = $this->getLinksPerPage($session);

        $this->getSearchQuery($session);
        $data['searchquery'] = $this->searchQuery;

        $data['page'] = $this->currentPage = (int) $req->query->get('page', 0);

        $orderBy = $req->query->get('orderby', 'id');
        if (array_key_exists($orderBy, $this->orderByColumns)) {
            $data['orderby'] = $this->orderBy = $orderBy;
        }
        $orderDir = $req->query->get('orderdir', 'desc');
        if (in_array($orderDir, ['asc', 'desc'])) {
            $data['orderdir'] = $this->orderDir = $orderDir;
        }

        $data['links'] = $links->getUsersLinks($this->getUser(), $this->searchQuery,
            [$orderBy => $orderDir], $this->linksPerPage,
            $this->currentPage * $this->linksPerPage);

        $data['amount'] = $links->getAmount($this->getUser(), $this->searchQuery);
        $data['pagination'] = $this->getPagination($data['amount']);
        $data['filter'] = $filter;

        return $this->render('sites/links/links.html.twig', ['data' => $data]);
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

    /**
     * Set search query
     *
     */
    #[Route(path: "/links/setquery", name: "links_setquery")]
    #[IsGranted('ROLE_USER', message: 'You are not allowed to access this site!')]
    public function setQuery(Request $req, SessionInterface $session)
    {
        $searchQuery = trim($req->query->get('searchquery'));
        if ($searchQuery != null) {
            $session->set('links-searchquery', $searchQuery);
        }
        return $this->redirectToRoute('links');
    }

    /**
     * Get search query
     *
     */
    private function getSearchQuery(SessionInterface $session)
    {
        $this->searchQuery = '';
        if ($session->has('links-searchquery')) {
            $this->searchQuery = $session->get('links-searchquery');
        }
    }

    /**
     * Reset search query
     *
     */
    #[Route(path: "/links/resetquery", name: "links_resetquery")]
    #[IsGranted('ROLE_USER', message: 'You are not allowed to access this site!')]
    public function resetQuery(SessionInterface $session)
    {
        $session->remove('links-searchquery');
        return $this->redirectToRoute('links');
    }

    /**
     * Set filter values for links
     *
     */
    #[Route(path: "/links/setfilter", name: "links_setfilter")]
    #[IsGranted('ROLE_USER', message: 'You are not allowed to access this site!')]
    public function setFilter(Request $req, SessionInterface $session)
    {
        if ($req->request->get('filter_action', '') == 'reset') {
            $filter = $this->getDefaultFilter();
            $session->set('links-filter', $filter);
        } else {
            $filter = $this->getDefaultFilter();
            if ($req->request->get('filter_only_with_clicks') == '1') {
                $filter['only_with_clicks'] = true;
                $filter['filter_is_active'] = true;
            }
            if ($req->request->get('filter_only_active_links') == '1') {
                $filter['only_active_links'] = true;
                $filter['filter_is_active'] = true;
            }
            if ($req->request->get('filter_only_my_favs') == '1') {
                $filter['only_my_favs'] = true;
                $filter['filter_is_active'] = true;
            }
            $session->set('links-filter', $filter);
        }
        return $this->redirectToRoute('links');
    }

    /**
     * Set Links per page
     */
    #[Route(path: "/links/setlinksperpage", name: "links_setlinksperpage")]
    #[IsGranted('ROLE_USER', message: 'You are not allowed to access this site!')]
    public function setLinksPerPage(Request $req, SessionInterface $session)
    {
        $linksPerPage = (int) $req->query->get('linksperpage');

        if ($linksPerPage != 0 AND
            in_array($linksPerPage, $this->linksPerPageChoices)) {
            $session->set('links-linksperpage', $linksPerPage);
        }
        return $this->redirectToRoute('links');
    }

    /**
     * Links per page
     */
    private function getLinksPerPage(SessionInterface $session)
    {
        $this->linksPerPage = $this->linksPerPageChoices[0];
        if ($session->has('links-linksperpage')) {
            $this->linksPerPage = (int) $session->get('links-linksperpage');
        }
        if ($this->linksPerPage == 0) {
            $this->linksPerPage = $this->linksPerPageChoices[0];
        }
        return $this->linksPerPage;
    }

    /**
     * Default filter values
     */
    private function getDefaultFilter() : array
    {
        return [
            'filter_is_active'  => false,
            'only_with_clicks'  => false,
            'only_active_links' => false,
            'only_my_favs'      => false,
        ];
    }

    /**
     * Get Filter values
     */
    private function getFilter(SessionInterface $session) : array
    {
        $filter = $this->getDefaultFilter();
        if ($session->has('links-filter')) {
            $nf = $session->get('links-filter');
            if (is_array($nf) AND count($nf) > 0) {
                $filter = $nf;
            }
        }
        return $filter;
    }

    /**
     * Pagination
     *
     */
    private function getPagination($amount)
    {
        $pagination = [
            'query'     => urlencode($this->searchQuery),
            'pages'     => [],
        ];

        $numberOfPages = ceil($amount / $this->linksPerPage);

        //if there are less than 10 pages
        if ($numberOfPages < 10) {

            for ($i=0; $i < $numberOfPages; $i++) {

                $start 	= $i * $this->linksPerPage + 1;
                $end 	= ($i + 1) * $this->linksPerPage;
                if ($end > $amount) {
                    $end = $amount;
                }
                if ($i == $this->currentPage) {
                    $pagination['pages'][] = [
                        'current' => true,
                        'label'   => $start." - ".$end,
                        'url'     => '',
                    ];
                }
                else {
                    $pagination['pages'][] = [
                        'current' => false,
                        'label'   => $start." - ".$end,
                        'url'     => 'page='.$i,
                    ];
                }
            }

        //if there are 10 or more pages
        } else {

            $leftBorder = $this->currentPage - 1;
            if ($leftBorder < 0) {
                $leftBorder = 0;
            }

            $rightBorder = $this->currentPage + 2;
            if ($rightBorder > $numberOfPages) {
                $rightBorder = $numberOfPages;
            }

            for ($i = $leftBorder; $i < $rightBorder; $i++) {
                $start 	= $i * $this->linksPerPage + 1;
                $end 	= ($i + 1) * $this->linksPerPage;
                if ($end > $amount){
                    $end = $amount;
                }
                if ($i == $this->currentPage) {
                    $pagination['pages'][] = [
                        'current' => true,
                        'label'   => $start." - ".$end,
                        'url'     => '',
                    ];
                }
                else {
                    $pagination['pages'][] = [
                        'current' => false,
                        'label'   => $start." - ".$end,
                        'url'     => 'page='.$i,
                    ];
                }
            }

            if ($leftBorder > 1) {
                array_unshift($pagination['pages'], [
                    'current' => false,
                    'label'   => '...',
                    'url'     => '',
                ]);
            }

            if ($leftBorder > 0) {
                array_unshift($pagination['pages'], [
                    'current' => false,
                    'label'   => '1 - '.$this->linksPerPage,
                    'url'     => 'page=0',
                ]);
            }

            if ($rightBorder < $numberOfPages-1) {
                $pagination['pages'][] = [
                    'current' => false,
                    'label'   => '...',
                    'url'     => '',
                ];
            }
            if ($rightBorder < $numberOfPages) {
                $pagination['pages'][] = [
                    'current' => false,
                    'label'   =>
                        ($this->linksPerPage * ($numberOfPages-1) +1).
                        ' - '.$amount,
                    'url'     => 'page='.($numberOfPages-1),
                ];
            }
        }
        return $pagination;
    }


}