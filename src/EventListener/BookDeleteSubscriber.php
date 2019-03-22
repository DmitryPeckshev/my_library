<?php
// src/EventListener/BookDeleteSubscriber.php
namespace App\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use App\Entity\Book;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class BookDeleteSubscriber implements EventSubscriber
{
    private $container;

    public function __construct(Container $container) {
        $this->container = $container;
    }

    public function getSubscribedEvents()
    {
        return [
            Events::postRemove,
        ];
    }

    public function postRemove(LifecycleEventArgs $args)
    {
        $this->index($args);
    }

    public function index(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        if ($entity instanceof Book) {
            $entityManager = $args->getObjectManager();
            
            if (file_exists($this->container->getParameter('covers_directory').'/'.$entity->getCover())) {
                unlink($this->container->getParameter('covers_directory').'/'.$entity->getCover());
            }
            if (file_exists($this->container->getParameter('books_directory').'/'.$entity->getFile())) {
                unlink($this->container->getParameter('books_directory').'/'.$entity->getFile());
            } 
        }
    }
}