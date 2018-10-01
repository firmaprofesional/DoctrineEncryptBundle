<?php

namespace FP\DoctrineEncryptBundle\Command;

use FP\DoctrineEncryptBundle\Interfaces\StandardizerInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Common\Collections\Collection;

/**
 * Description of ODMOM
 *
 * @author wpigott
 */
class ODMOM implements StandardizerInterface {

    private $objectManager;

    public function __construct(DocumentManager $documentManager) {
        $this->objectManager = $documentManager;
    }

    /**
     * 
     * @return DocumentManager
     */
    public function getObjectManager() {
        return $this->objectManager;
    }

    public function scheduleObjectForUpdate($object) {
        $metadata = $this->getObjectManager()->getClassMetadata(get_class($object));

        foreach ($metadata->getFieldNames() as $fieldName) {
            if ($metadata->hasEmbed($fieldName)) {
                $this->handleEmbed($object, $metadata, $fieldName);
            }
        }

        // A hackish way to force the object to be flagged as 
        // updated (so it triggers the events) when it really is not.
        $this->getObjectManager()->getUnitOfWork()->setOriginalDocumentData($object, array('__fp_changer' => '1'));
    }

    private function handleEmbed($object, $metadata, $fieldName) {
        $fieldValue = $metadata->getFieldValue($object, $fieldName);

        if ($fieldValue instanceof Collection) {
            foreach ($fieldValue as $fieldObject) {
                $this->scheduleObjectForUpdate($fieldObject);
            }
        } elseif (is_object($fieldValue)) {
            $this->scheduleObjectForUpdate($fieldValue);
        }
    }

}
