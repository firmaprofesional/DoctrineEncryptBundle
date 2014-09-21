<?php

namespace TDM\DoctrineEncryptBundle\Interfaces;

use Doctrine\Common\Persistence\ObjectManager;
use \ReflectionClass;

/**
 *
 * @author Westin Pigott
 */
interface ObjectWrapperInterface {

    public function __construct(ObjectManager $objectManager, $object, $isEncrypt);

    public function getHash();

    public function getObjectManager();

    public function getObject();

    /**
     * @return ReflectionClass Description
     */
    public function getReflection();

    public function getIsEncrypt();
}
