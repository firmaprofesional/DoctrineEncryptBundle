<?php

namespace FP\DoctrineEncryptBundle\Subscribers;

use FP\DoctrineEncryptBundle\Subscribers\AbstractDoctrineEncryptSubscriber;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use \ReflectionClass;

/**
 * Description of AbstractORMDoctrineEncryptSubscriber
 *
 * @author wpigott
 */
abstract class AbstractORMDoctrineEncryptSubscriber extends AbstractDoctrineEncryptSubscriber {

    /**
     * Realization of EventSubscriber interface method.
     * @return Array Return all events which this subscriber is listening
     */
    public function getSubscribedEvents() {
        return array(
            Events::prePersist,
            Events::preUpdate,
            Events::postLoad,
        );
    }

    /**
     * Listen a prePersist lifecycle event. Checking and encrypt entities
     * which have <code>@Encrypted</code> annotation
     * @param LifecycleEventArgs $args 
     */
    public function prePersist($args) {
        if (!$args instanceof LifecycleEventArgs)
            throw new \InvalidArgumentException('Invalid argument passed.');

        $this->processFields($args->getEntity());
    }

    /**
     * Listen a preUpdate lifecycle event. Checking and encrypt entities fields
     * which have <code>@Encrypted</code> annotation. Using changesets to avoid preUpdate event
     * restrictions
     * @param LifecycleEventArgs $args 
     */
    public function preUpdate($args) {
        if (!$args instanceof PreUpdateEventArgs)
            throw new \InvalidArgumentException('Invalid argument passed.');

        $entity = $args->getEntity();
        $this->processFields($entity);

        $om = $args->getEntityManager();
        if (!$om->getUnitOfWork()->isScheduledForDelete($entity)) {
            $om->getUnitOfWork()->recomputeSingleEntityChangeSet($om->getClassMetadata(get_class($entity)), $entity);
        }
    }
    
    /**
     * Listen a postUpdate lifecycle event. Checking and decrypt entities
     * which have @Encrypted annotations
     * @param LifecycleEventArgs $args 
     */
    public function postUpdate($args) {
        $this->postLoad($args);
    }
    
    /**
     * Listen a postPersist lifecycle event. Checking and decrypt entities
     * which have @Encrypted annotations
     * @param LifecycleEventArgs $args 
     */
    public function postPersist($args) {
        $this->postLoad($args);
    }

    /**
     * Listen a postLoad lifecycle event. Checking and decrypt entities
     * which have <code>@Encrypted</code> annotations
     * @param LifecycleEventArgs $args 
     */
    public function postLoad($args) {
        if (!$args instanceof LifecycleEventArgs)
            throw new \InvalidArgumentException('Invalid argument passed.');

        $entity = $args->getEntity();
        if (!$this->hasInDecodedRegistry($entity, $args->getEntityManager())) {
            if ($this->processFields($entity, false)) {
                $this->addToDecodedRegistry($entity, $args->getEntityManager());
            }
        }
    }

}

?>
