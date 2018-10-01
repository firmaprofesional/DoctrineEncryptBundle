<?php

namespace FP\DoctrineEncryptBundle\Subscribers;

use FP\DoctrineEncryptBundle\Subscribers\AbstractDoctrineEncryptSubscriber;
use FP\DoctrineEncryptBundle\Configuration\Encrypted;
use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Doctrine\ODM\MongoDB\Event\PreUpdateEventArgs;
use \ReflectionClass;
use \ReflectionProperty;

/**
 * Description of AbstractODMDoctrineEncryptSubscriber
 *
 * @author wpigott
 */
abstract class AbstractODMDoctrineEncryptSubscriber extends AbstractDoctrineEncryptSubscriber {

    /**
     * Listen a prePersist lifecycle event. Checking and encrypt entities
     * which have <code>@Encrypted</code> annotation
     * @param LifecycleEventArgs $args 
     */
    public function prePersist($args) {
        if (!$args instanceof LifecycleEventArgs)
            throw new \InvalidArgumentException('Invalid argument passed.');

        $this->processFields($args->getDocument());
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

        $om = $args->getDocumentManager();
        $document = $args->getDocument();
        $this->processFields($document);
        if (!$om->getUnitOfWork()->isScheduledForDelete($document)) {
            $om->getUnitOfWork()->recomputeSingleDocumentChangeSet($om->getClassMetadata(get_class($document)), $document);
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

        $document = $args->getDocument();
        //if (!$this->hasInDecodedRegistry($document, $args->getDocumentManager())) {
            if ($this->processFields($document, false)) {
                $this->addToDecodedRegistry($document, $args->getDocumentManager());
            }
        //}
    }

    /**
     * We override the standard implementation so we can deal with arrays.
     * @param type $encryptorMethod
     * @param type $value
     * @param type $deterministic
     * @return type
     */
    protected function handleValue($encryptorMethod, $value, $deterministic) {
        if (is_array($value)) {
            $new = array();
            foreach ($value as $key => $subValue) {
                $new[$key] = parent::handleValue($encryptorMethod, $subValue, $deterministic);
            }
            return $new;
        }
        return parent::handleValue($encryptorMethod, $value, $deterministic);
    }

}

?>
