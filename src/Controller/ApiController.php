<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Book;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use JMS\Serializer\SerializerBuilder;


/**
 * @Route("/api/v1/books")
 */
class ApiController extends AbstractController
{

    /**
    * @var $request Request
    *
    * @return bool|string
    */
    private function checkKey($request) {
        $key = $request->get('apiKey');
        if(empty($key)){
            return 'empty apiKey';
        }elseif($key != $this->getParameter('apiKey')){
            return 'wrong apiKey';
        }else{
            return false;
        }
    }


    /**
     * @Route("/")
     *
     * @Method("GET")
     * @return json
     */
    public function getAll(Request $request) {

        $serializer = SerializerBuilder::create()->build();

        if($error = $this->checkKey($request)){
            return new Response(
                $serializer->serialize($error, 'json'),
                Response::HTTP_BAD_REQUEST,
                ['content-type' => 'application/json']
            );
        }

        $repository = $this->getDoctrine()->getRepository('App:Book');
        $allBooks = $repository->findBy(array(), array('date' => 'ASC'));
        $baseurl = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath();
        foreach ($allBooks as $book) {
            $book->setCover($baseurl.'/'.$this->getParameter('covers_directory').'/'.$book->getCover());
            if($book->getAllow()){
                $book->setFile($baseurl.'/'.$this->getParameter('books_directory').'/'.$book->getFile());
            }else{
                $book->setFile('');
            }
        }

        $jsonContent = $serializer->serialize($allBooks, 'json');

        return new Response(
            $serializer->serialize($allBooks, 'json'),
            Response::HTTP_OK,
            ['content-type' => 'application/json']
        );
    }


    /**
     * @Route("/add")
     *
     * @Method("GET")
     * @return json
     */
    public function addBook(Request $request) {

        $serializer = SerializerBuilder::create()->build();

        if($checkKey = $this->checkKey($request)){
            $error = $checkKey;
        }elseif(!$name = $request->get('name')){
            $error = 'field name is empty!';
        }elseif(!$author = $request->get('author')){
            $error = 'field author is empty!';
        }elseif(!$date = $request->get('date')){
            $error = 'field date is empty!';
        }
        $allow = boolval($request->get('allow'));

        if(!empty($error)){
            return new Response(
                $serializer->serialize($error, 'json'),
                Response::HTTP_BAD_REQUEST,
                ['content-type' => 'application/json']
            );
        }else{

            $em = $this->getDoctrine()->getManager();
            $newBook = new Book();

            $newBook->setName($name);
            $newBook->setAuthor($author);
            $newBook->setDate(new \DateTime($date));
            $newBook->setAllow($allow);

            $newBook->setFile('no-file.jpg');
            $newBook->setCover('no-cover.jpg');

            $em->persist($newBook);
            $em->flush();

            $cache = new FilesystemAdapter();
            $cache->deleteItem('cache_id');

            return new Response(
                $serializer->serialize('book added', 'json'),
                Response::HTTP_OK,
                ['content-type' => 'application/json']
            );  
        }
    }


    /**
     * @Route("/{id}/edit", requirements={"id": "\d+"})
     *
     * @Method("GET")
     * @return json
     */
    public function editBook($id, Request $request) {

        $serializer = SerializerBuilder::create()->build();

        if($error = $this->checkKey($request)){
            return new Response(
                $serializer->serialize($error, 'json'),
                Response::HTTP_BAD_REQUEST,
                ['content-type' => 'application/json']
            );
        }

        $em = $this->getDoctrine()->getManager();
        $editedBook = $em->getRepository('App:Book')->find($id);

        if($name = $request->get('name')){
            $editedBook->setName($name);
        }
        if($author = $request->get('author')){
            $editedBook->setAuthor($author);
        }
        if($date = $request->get('date')){
            $editedBook->setDate(new \DateTime($date));
        }
        if($request->get('allow') !== null){
            $editedBook->setAllow(boolval($request->get('allow')));
        }

        $em->flush();

        $cache = new FilesystemAdapter();
        $cache->deleteItem('cache_id');

        return new Response(
            $serializer->serialize('book modified', 'json'),
            Response::HTTP_OK,
            ['content-type' => 'application/json']
        );  
    }

}
