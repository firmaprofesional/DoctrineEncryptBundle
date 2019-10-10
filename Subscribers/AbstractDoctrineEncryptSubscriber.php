<?php

namespace FP\DoctrineEncryptBundle\Subscribers;

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Persistence\ObjectManager;
use \ReflectionClass;
use FP\DoctrineEncryptBundle\Encryptors\EncryptorInterface;
use FP\DoctrineEncryptBundle\Configuration\Encrypted;
use \ReflectionProperty;
use \Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\Mapping\MappingException;

/**
 * Doctrine event subscriber which encrypt/decrypt entities
 */
abstract class AbstractDoctrineEncryptSubscriber implements EventSubscriber {

    /**
     * Encrypted annotation full name
     */
    const ENCRYPTED_ANN_NAME = 'FP\DoctrineEncryptBundle\Configuration\Encrypted';
    
    /**
     * Encryptor
     * @var EncryptorInterface 
     */
    protected $encryptor;

    /**
     * Annotation reader
     * @var Doctrine\Common\Annotations\Reader
     */
    protected $annReader;

    /**
     * Registr to avoid multi decode operations for one entity
     * @var array
     */
    protected $decodedRegistry = array();

    /**
     * Initialization of subscriber
     * @param string $encryptorClass  The encryptor class.  This can be empty if 
     * a service is being provided.
     * @param string $secretKey The secret key. 
     * @param EncryptorServiceInterface|NULL $service (Optional)  An EncryptorServiceInterface.  
     * This allows for the use of dependency injection for the encrypters.
     */
    public function __construct(Reader $annReader, EncryptorInterface $service) {
        $this->annReader = $annReader;
        $this->encryptor = $service;
    }

    /**
     * Listen a prePersist lifecycle event. Checking and encrypt entities
     * which have <code>@Encrypted</code> annotation
     * @param LifecycleEventArgs $args 
     */
    abstract public function prePersist($args);

    /**
     * Listen a preUpdate lifecycle event. Checking and encrypt entities fields
     * which have <code>@Encrypted</code> annotation. Using changesets to avoid preUpdate event
     * restrictions
     * @param LifecycleEventArgs $args 
     */
    abstract public function preUpdate($args);

    /**
     * Listen a postLoad lifecycle event. Checking and decrypt entities
     * which have <code>@Encrypted</code> annotations
     * @param LifecycleEventArgs $args 
     */
    abstract public function postLoad($args);

    /**
     * Realization of EventSubscriber interface method.
     * @return Array Return all events which this subscriber is listening
     */
    abstract public function getSubscribedEvents();

    /**
     * Capitalize string
     * @param string $word
     * @return string
     */
    public static function capitalize($word) {
        if (is_array($word)) {
            $word = $word[0];
        }

        return str_replace(' ', '', ucwords(str_replace(array('-', '_'), ' ', $word)));
    }

    /**
     * Process (encrypt/decrypt) entities fields
     * @param Obj $object Some doctrine entity
     * @param Boolean $isEncryptOperation If true - encrypt, false - decrypt entity 
     */
    protected function processFields($object, $isEncryptOperation = true) {
        $encryptorMethod = $isEncryptOperation ? 'encrypt' : 'decrypt';
        $properties = $this->getReflectionProperties($object);
        $withAnnotation = false;
        foreach ($properties as $refProperty) {
            if ($this->processSingleField($object, $refProperty, $encryptorMethod)) {
                $withAnnotation = TRUE;
            }
        }
        return $withAnnotation;
    }

    private function processSingleField($object, ReflectionProperty $refProperty, $encryptorMethod) {
        $annotation = $this->getAnnotation($refProperty);
        if (NULL === $annotation) {
            return FALSE;
        }

        if (!(($annotation->getDecrypt()) && ('encrypt' === $encryptorMethod))) {
            $refProperty->setAccessible(TRUE);
            $refProperty->setValue($object, $this->determineNewValue($annotation, $refProperty->getValue($object), $object, $encryptorMethod));
        }
        return TRUE;
    }

    private function determineNewValue(Encrypted $annotation, $currentValue, $object, $encryptorMethod) {
        //Check if there is a custom handler for the field.
        $customMethod = $annotation->getHandlerMethod();
        $customService = $annotation->getHandlerService();
        if ((NULL !== $customService) && (NULL !== $customMethod)) {
            $service = $this->getService($customService);
            // Check that the method is valid.
            if (!is_callable(array($service, $customMethod))) {
                throw new Exception('Method "' . $customMethod . '" is not a callable method.');
            }
            return $service->$customMethod($this->encryptor, $currentValue, $encryptorMethod);
        }
        return $this->handleValue($encryptorMethod, $currentValue, $annotation->getDeterministic());
    }


    /**
     * This method can be overridden to handle a specific data type differently.  
     * IE.  Override this to handle arrays specifically with MongoDB.
     * @param type $encryptorMethod
     * @param type $value
     * @param type $deterministic
     */
    protected function handleValue($encryptorMethod, $value, $deterministic) {
        return $this->encryptor->$encryptorMethod($value, $deterministic);
    }

    /**
     * Check if we have entity in decoded registry
     * @param Object $entity Some doctrine entity
     * @param Doctrine\Common\Persistence\ObjectManager $em
     * @return boolean
     */
    protected function hasInDecodedRegistry($entity, ObjectManager $om) {
        $className = get_class($entity);
        $metadata = $om->getClassMetadata($className);
        try {
            $identifier = $metadata->getSingleIdentifierFieldName();
        } catch (MappingException $e) {
            return FALSE;
        }
        $reflectionClass = new ReflectionClass($entity);
        if (!$reflectionClass->hasProperty($identifier)) {
            $reflectionClass = $reflectionClass->getParentClass();
        }
        $property = $reflectionClass->getProperty($identifier);
        $property->setAccessible(true);
        return isset($this->decodedRegistry[$className][$property->getValue($entity)]);
    }

    /**
     * Adds entity to decoded registry
     * @param object $entity Some doctrine entity
     * @param Doctrine\Common\Persistence\ObjectManager $em
     */
    protected function addToDecodedRegistry($entity, ObjectManager $om) {
        return;
        $className = get_class($entity);
        $metadata = $om->getClassMetadata($className);
        try {
            $identifier = $metadata->getSingleIdentifierFieldName();
        } catch (MappingException $e) {
            return FALSE;
        }
        if ($entity instanceof \Doctrine\Common\Persistence\Proxy) {
            $reflectionClass = $reflectionClass->getParentClass();
        }
        $property = $reflectionClass->getProperty($identifier);
        $property->setAccessible(true);
        $this->decodedRegistry[$className][$property->getValue($entity)] = true;
    }

    /**
     *
     * @param ReflectionProperty $reflectionProperty
     * @return Encrypted|NULL
     */
    protected function getAnnotation(ReflectionProperty $reflectionProperty) {
        return $this->annReader->getPropertyAnnotation($reflectionProperty, self::ENCRYPTED_ANN_NAME);
    }

    /**
     * 
     * @param mixed $object
     * @return ReflectionProperty[]
     */
    protected function getReflectionProperties($object) {
        $reflectionClass = new ReflectionClass($object);
        return $reflectionClass->getProperties();
    }

}
