<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Entity\Book;

class LibraryController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request) {
        
        $repository = $this->getDoctrine()->getRepository('AppBundle:Book');
        $allBooks = $repository->findAll();

        foreach($allBooks as $oneBook){
            $oneBook->setDate($oneBook->getDate()->format('d.m.Y'));       
        }

        $response = $this->render('AppBundle:Page:index.html.twig', array('allBooks' => $allBooks, )); 
        $response->setSharedMaxAge(86400);
        $response->setEtag(md5($response->getContent()));
        $response->setPublic();
        if ($response->isNotModified($request)) {
            return $response;
        }

        return $response;
    }

    /**
     * @Route("/delete/{slug}", name="book_delete", requirements={"slug": "\d+"})
     */
    public function delBookAction($slug, Request $request) {

        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'You cannot edit this item.');

        $em = $this->getDoctrine()->getManager();
        $currentBook = $em->getRepository('AppBundle:Book')->find($slug);
 
        if (file_exists($this->getParameter('covers_directory').'/'.$currentBook->getCover())) {
            unlink($this->getParameter('covers_directory').'/'.$currentBook->getCover());
        }
        if (file_exists($this->getParameter('books_directory').'/'.$currentBook->getFile())) {
            unlink($this->getParameter('books_directory').'/'.$currentBook->getFile());
        } 

        $em->remove($currentBook);
        $em->flush();
        return $this->redirectToRoute('homepage');
    }


}
