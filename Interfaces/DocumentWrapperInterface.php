<?php

namespace TDM\DoctrineEncryptBundle\Interfaces;

use TDM\DoctrineEncryptBundle\Interfaces\ObjectWrapperInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\UnitOfWork;

/**
 *
 * @author Westin Pigott
 */
interface DocumentWrapperInterface extends ObjectWrapperInterface {

    /**
     * 
     * @return DocumentManager
     */
    public function getDocumentManager();

    /**
     * 
     * @return UnitOfWork
     */
    public function getUOW();
}
