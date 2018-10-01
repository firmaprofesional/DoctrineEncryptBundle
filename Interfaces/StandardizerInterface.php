<?php

namespace FP\DoctrineEncryptBundle\Interfaces;

use Doctrine\Common\Persistence\ObjectManager;

/**
 *
 * @author wpigott
 */
interface StandardizerInterface {

    /**
     * @return ObjectManager
     */
    public function getObjectManager();

    public function scheduleObjectForUpdate($object);
}
