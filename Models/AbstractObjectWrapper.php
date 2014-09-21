<?php

namespace TDM\DoctrineEncryptBundle\Models;

use TDM\DoctrineEncryptBundle\Interfaces\ObjectWrapperInterface;
use \ReflectionClass;
use \ReflectionProperty;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Description of AbstractObjectWrapper
 *
 * @author Westin Pigott
 */
abstract class AbstractObjectWrapper implements ObjectWrapperInterface {

    private $hash;
    protected $objectManager;
    protected $object;
    private $reflection;
    private $isEncrypt;

    abstract protected function validateInputs();

    public function __construct(ObjectManager $objectManager, $object, $isEncrypt) {
        $this->objectManager = $objectManager;
        $this->object = $object;
        $this->isEncrypt = $isEncrypt;
        $this->hash = spl_object_hash($object);
    }

    public function getHash() {
        return $this->hash;
    }

    public function getObjectManager() {
        return $this->objectManager;
    }

    public function getObject() {
        return $this->object;
    }

    public function getReflection() {
        if (NULL === $this->reflection) {
            $this->reflection = new \ReflectionClass($this->object);
        }
        return $this->reflection;
    }

    public function getIsEncrypt() {
        return $this->isEncrypt;
    }

}
