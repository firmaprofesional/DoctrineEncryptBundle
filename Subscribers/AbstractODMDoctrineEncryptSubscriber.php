<?php

namespace TDM\DoctrineEncryptBundle\Subscribers;

use TDM\DoctrineEncryptBundle\Subscribers\AbstractDoctrineEncryptSubscriber;
use TDM\DoctrineEncryptBundle\Configuration\Encrypted;
use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Doctrine\ODM\MongoDB\Event\PreUpdateEventArgs;
use Doctrine\ODM\MongoDB\Event\OnFlushEventArgs;
use \ReflectionClass;
use \ReflectionProperty;
use TDM\DoctrineEncryptBundle\Models\DocumentWrapper;
use TDM\DoctrineEncryptBundle\Interfaces\DocumentWrapperInterface;
use TDM\DoctrineEncryptBundle\Interfaces\ObjectWrapperInterface;

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

        // Make a documentwrapper for passing around.
        $documentWrapper = new DocumentWrapper($args->getDocumentManager(), $args->getDocument(), TRUE);


        $this->processFields($documentWrapper);
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

        // Make a documentwrapper for passing around.
        $documentWrapper = new DocumentWrapper($args->getDocumentManager(), $args->getDocument(), TRUE);
        $om = $args->getDocumentManager();
        $document = $args->getDocument();
        $this->processFields($documentWrapper);
        if (!$om->getUnitOfWork()->isScheduledForDelete($document)) {
            $om->getUnitOfWork()->recomputeSingleDocumentChangeSet($om->getClassMetadata(get_class($document)), $document);
        }
    }

    public function onFlush($args) {
        if (!$args instanceof OnFlushEventArgs)
            throw new \InvalidArgumentException('Invalid argument passed.');

        $uow = $args->getDocumentManager()->getUnitOfWork();

        $docs = $uow->getScheduledDocumentUpdates();
        foreach ($docs as $doc) {
            // Make a documentwrapper for passing around.
            $documentWrapper = new DocumentWrapper($args->getDocumentManager(), $doc, TRUE);
            $this->onFlushSingle($documentWrapper);
        }
    }

    private function onFlushSingle(DocumentWrapperInterface $documentWrapper) {
        if ($this->processFields($documentWrapper)) {
            $documentWrapper->getUOW()->computeChangeSet($documentWrapper->getDocumentManager()->getClassMetadata(get_class($documentWrapper->getObject())), $documentWrapper->getObject());

            // Now revert 
            foreach ($documentWrapper->getUOW()->getDocumentChangeSet($documentWrapper->getObject()) as $fieldName => $change) {
                $property = $documentWrapper->getReflection()->getProperty($fieldName);
                $property->setAccessible(TRUE);
                $property->setValue($documentWrapper->getObject(), $change[0]);
            }
        }
    }

    protected function checkFieldShouldProcess(ObjectWrapperInterface $objectWrapper, $fieldName) {
        if (!$objectWrapper instanceof DocumentWrapperInterface) {
            throw new \RuntimeException('ObjectWrapper must be an instanceof DocumentWrapperInterface for ODM use.');
        }
        if (!$objectWrapper->getIsEncrypt()) {
            return TRUE;
        }
        //$name = $objectWrapper->getDocumentManager()->getClassMetadata(get_class($objectWrapper->getObject()))->getFieldMapping($fieldName)['name'];
        return array_key_exists($fieldName, $objectWrapper->getUOW()->getDocumentChangeSet($objectWrapper->getObject()));
    }

    /**
     * Listen a postLoad lifecycle event. Checking and decrypt entities
     * which have <code>@Encrypted</code> annotations
     * @param LifecycleEventArgs $args 
     */
    public function postLoad($args) {

        if (!$args instanceof LifecycleEventArgs)
            throw new \InvalidArgumentException('Invalid argument passed.');

        // Make a documentwrapper for passing around.
        $documentWrapper = new DocumentWrapper($args->getDocumentManager(), $args->getDocument(), FALSE);

        if ($this->processFields($documentWrapper)) {
            $this->updateOriginalData($documentWrapper);
        }
    }

    private function updateOriginalData(DocumentWrapperInterface $documentWrapper) {
        $document = $documentWrapper->getObject();

        // Parse field mapping for fast reference
        $meta = $documentWrapper->getDocumentManager()->getClassMetadata(get_class($document));
        $parsedMap = array('type' => array(), 'fieldName' => array());
        foreach ($meta->getFieldNames() as $name) {
            $temp = $meta->getFieldMapping($name);
            $parsedMap['type'][$temp['name']] = $temp['type'];
            $parsedMap['fieldName'][$temp['name']] = $temp['fieldName'];
        }

        // Generate final data array.  Do not change values which are of type "id"
        $finalData = array();
        foreach ($documentWrapper->getUOW()->getOriginalDocumentData($document) as $key => $value) {
            //Check it is not the ID field.
            if ($parsedMap['type'][$key] === 'id') {
                $finalData[$key] = $value;
                continue;
            }
            $property = $documentWrapper->getReflection()->getProperty($parsedMap['fieldName'][$key]);
            $property->setAccessible(TRUE);
            $newValue = $property->getValue($document);
            $finalData[$key] = $newValue;
        }
        $documentWrapper->getUOW()->setOriginalDocumentData($document, $finalData);
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
