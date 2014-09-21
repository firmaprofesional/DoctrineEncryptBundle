<?php

namespace TDM\DoctrineEncryptBundle\Models;

use TDM\DoctrineEncryptBundle\Models\AbstractObjectWrapper;
use TDM\DoctrineEncryptBundle\Interfaces\DocumentWrapperInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\UnitOfWork;
use \RuntimeException;

/**
 * Description of DocumentWrapper
 *
 * @author Westin Pigott
 */
class DocumentWrapper extends AbstractObjectWrapper implements DocumentWrapperInterface {

    protected function validateInputs() {
        if (!$this->objectManager instanceof DocumentManager) {
            throw new RuntimeException('ObjectManager must be of type DocumentManager when working with ODM.');
        }
    }

    /**
     * 
     * @return DocumentManager
     */
    public function getDocumentManager() {
        return $this->getObjectManager();
    }

    /**
     * 
     * @return UnitOfWork
     */
    public function getUOW() {
        return $this->getDocumentManager()->getUnitOfWork();
    }

}
