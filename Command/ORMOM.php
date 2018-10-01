<?php

namespace FP\DoctrineEncryptBundle\Command;

use FP\DoctrineEncryptBundle\Interfaces\StandardizerInterface;
use Doctrine\ORM\EntityManager;

/**
 * Description of ODMOM
 *
 * @author wpigott
 */
class ORMOM implements StandardizerInterface {

    private $objectManager;

    public function __construct(EntityManager $documentManager) {
        $this->objectManager = $documentManager;
    }

    /**
     * 
     * @return EntityManager
     */
    public function getObjectManager() {
        return $this->objectManager;
    }
    
    public function scheduleObjectForUpdate($object) {
        // A hackish way to force the object to be flagged as 
        // updated (so it triggers the events) when it really is not.
        $this->getObjectManager()->getUnitOfWork()->setOriginalEntityData($object, array('__fp_changer'=>'1'));
    }

}
