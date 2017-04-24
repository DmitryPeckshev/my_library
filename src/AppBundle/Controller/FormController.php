<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Book;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\ImageType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;


class FormController extends Controller
{   
    /**
    * @Route("/new/", name="newbook")
    */
    public function newBookAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'You cannot edit this item.');

        $book = new Book();
        $form = $this->createFormBuilder($book)
            ->add('name', TextType::class)
            ->add('author', TextType::class)
            ->add('cover', FileType::class)
            ->add('file', FileType::class)
            ->add('date', DateType::class, array('widget' => 'single_text'))
            ->add('allow', CheckboxType::class, array('required' => false))
            ->add('submit', SubmitType::class)
            ->getForm();   
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
            return $this->redirectToRoute('newbook');
        }else{
            return $this->render('AppBundle:Page:newbook.html.twig', array('currentBook' => false, 'form'=>$form->createView(), 'modify'=>false,));
        }
    }


    /**
    * @Route("/modify/{slug}", name="book_modify", requirements={"slug": "\d+"})
    */
    public function modifyBookAction($slug, Request $request){

        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'You cannot edit this item.');

        $em = $this->getDoctrine()->getManager();
        $currentBook = $em->getRepository('AppBundle:Book')->find($slug);

        $book = new Book();
        $oldCover = new File($this->getParameter('covers_directory').'/'.$currentBook->getCover());
        $oldFile = new File($this->getParameter('books_directory').'/'.$currentBook->getFile());
        $form = $this->createFormBuilder($book)
            ->add('name', TextType::class, array('data' => $currentBook->getName()))
            ->add('author', TextType::class, array('data' => $currentBook->getAuthor()))
            ->add('cover', FileType::class, array('data' => $oldCover, 'required' => false))
            ->add('file', FileType::class, array('data' => $oldFile, 'required' => false))
            ->add('date', DateType::class, array('widget' => 'single_text', 'data' => $currentBook->getDate()))
            ->add('allow', CheckboxType::class, array('required' => false, 'data' => $currentBook->getAllow()))
            ->add('submit', SubmitType::class)
            ->getForm();
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
            
            return $this->redirectToRoute('homepage');
        }else{
            return $this->render('AppBundle:Page:newbook.html.twig', array('currentBook' => $currentBook, 'form'=>$form->createView(), 'modify'=>true,));
        }     

    }


}
