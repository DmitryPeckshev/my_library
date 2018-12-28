<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Book;
use App\Form\Form;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;



class LibraryController extends AbstractController
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request) {

        $cache = new FilesystemAdapter();
        $cacheBooks = $cache->getItem('cache_id');
        if ($cacheBooks->isHit()) {
            $allBooks = $cacheBooks->get();
        }else{
            $repository = $this->getDoctrine()->getRepository('App:Book');
            $allBooks = $repository->findBy(array(), array('date' => 'ASC'));
            $cacheBooks->expiresAfter(86400);
            $cacheBooks->set($allBooks);
            $cache->save($cacheBooks);
        }

        return $this->render('Page/index.html.twig', array('allBooks' => $allBooks, ));
    }


    /**
     * @Route("/delete/{slug}", name="book_delete", requirements={"slug": "\d+"})
     */
    public function delBookAction($slug, Request $request) {

        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'You cannot edit this item.');

        $em = $this->getDoctrine()->getManager();
        $currentBook = $em->getRepository('App:Book')->find($slug);
 
        if (file_exists($this->getParameter('covers_directory').'/'.$currentBook->getCover())) {
            unlink($this->getParameter('covers_directory').'/'.$currentBook->getCover());
        }
        if (file_exists($this->getParameter('books_directory').'/'.$currentBook->getFile())) {
            unlink($this->getParameter('books_directory').'/'.$currentBook->getFile());
        } 

        $em->remove($currentBook);
        $em->flush();

        $cache = new FilesystemAdapter();
        $cache->deleteItem('cache_id');

        return $this->redirectToRoute('homepage');
    }


     /**
    * @Route("/new/", name="newbook")
    */
    public function newBookAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'You cannot edit this item.');

        $book = new Book();
        $form = $this->createForm(Form::class,  $book);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $book = $form->getData();
            $em = $this->getDoctrine()->getManager();

            $file = $book->getFile();
            $cover = $book->getCover();
            $fileName = $book->getAuthor().' - '.$book->getName().'.'.$file->guessExtension();
            $coverName = $book->getAuthor().' - '.$book->getName().'.'.$cover->guessExtension();
            $year = $book->getDate()->format('Y');
            $file->move(
                $this->getParameter('books_directory').'/'.$year,
                $fileName
            );
            $cover->move(
                $this->getParameter('covers_directory').'/'.$year,
                $coverName
            );
            $book->setFile($year.'/'.$fileName);
            $book->setCover($year.'/'.$coverName);

            $em->persist($book);
            $em->flush();

            $cache = new FilesystemAdapter();
            $cache->deleteItem('cache_id');

            return $this->redirectToRoute('newbook');
        }else{
            return $this->render('Page/newbook.html.twig', array('currentBook' => false, 'form'=>$form->createView(), 'modify'=>false,));
        }
    }


    /**
    * @Route("/modify/{slug}", name="book_modify", requirements={"slug": "\d+"})
    */
    public function modifyBookAction($slug, Request $request){

        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'You cannot edit this item.');

        $em = $this->getDoctrine()->getManager();
        $currentBook = $em->getRepository('App:Book')->find($slug);

        $book = new Book();
        $form = $this->createForm(Form::class,  $book);

        $form->get('name')->setData($currentBook->getName());
        $form->get('author')->setData($currentBook->getAuthor());
        $form->get('date')->setData($currentBook->getDate());
        $form->get('allow')->setData($currentBook->getAllow());

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $book = $form->getData();
            $currentBook->setName($book->getName());
            $currentBook->setAuthor($book->getAuthor());
            if($book->getCover() != null){
                $cover = $book->getCover();
                $year = $book->getDate()->format('Y');
                $coverName = $book->getAuthor().' - '.$book->getName().'.'.$cover->guessExtension();
                unlink($this->getParameter('covers_directory').'/'.$currentBook->getCover());
                $cover->move(
                    $this->getParameter('covers_directory').'/'.$year,
                    $coverName
                );
                $currentBook->setCover($year.'/'.$coverName);
                
            }
            if($book->getFile() != null){
                $file = $book->getFile();
                $year = $book->getDate()->format('Y');
                $fileName = $book->getAuthor().' - '.$book->getName().'.'.$file->guessExtension();
                unlink($this->getParameter('books_directory').'/'.$currentBook->getFile());
                $file->move(
                    $this->getParameter('books_directory').'/'.$year,
                    $fileName
                );
                $currentBook->setFile($year.'/'.$fileName);
            }
            $currentBook->setDate($book->getDate());
            $currentBook->setAllow($book->getAllow());
            $em->flush();

            $cache = new FilesystemAdapter();
            $cache->deleteItem('cache_id');
            
            return $this->redirectToRoute('homepage');
        }else{
            return $this->render('Page/newbook.html.twig', array('currentBook' => $currentBook, 'form'=>$form->createView(), 'modify'=>true,));
        }  

    }


}
