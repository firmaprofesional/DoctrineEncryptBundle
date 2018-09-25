<?php

namespace TDM\DoctrineEncryptBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

/**
 * Description of RegisterServiceCompilerPass
 *
 * @author wpigott
 */
class RegisterServiceCompilerPass implements CompilerPassInterface {

    public function process(ContainerBuilder $container) {

        // Load some parameters
        $secretKey = $container->getParameter('tdm_doctrine_encrypt.secret_key');
        $systemSalt = $container->getParameter('tdm_doctrine_encrypt.system_salt');
        $encryptorServiceId = $container->getParameter('tdm_doctrine_encrypt.encryptor_service');
        $prefix = $container->getParameter('tdm_doctrine_encrypt.encrypted_prefix');

        // Set the arguments for the encryptor service and add alias
        $this->getDefinition($container, $encryptorServiceId)->setArguments(array($secretKey, $systemSalt, $prefix));
        $encrypterServiceReference = new Reference($encryptorServiceId);
        
        // Add service as argument on listeners.
        foreach($container->getParameter('tdm_doctrine_encrypt.db_driver') as $db_driver){
            $this->getDefinition($container, 'tdm_doctrine_encrypt.'.$db_driver.'_subscriber.encrypt')->addArgument($encrypterServiceReference);
            $this->getDefinition($container, 'tdm_doctrine_encrypt.'.$db_driver.'_subscriber.decrypt')->addArgument($encrypterServiceReference);
        }
    }

    /**
     * 
     * @param ContainerBuilder $container
     * @param string $id
     * @return Definition
     * @throws \RuntimeException
     */
    private function getDefinition(ContainerBuilder $container, $id) {
        try {
            return $container->findDefinition($id);
        } catch (InvalidArgumentException $e) {
            throw new \RuntimeException('Unable to locate service (' . $id . ').', NULL, $e);
        }
    }

}

?>
