<?php

namespace FP\DoctrineEncryptBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration tree for security bundle. Full tree you can see in Resources/docs
 * 
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface {
    const defaultPrefix = '_ENC_';
    const defaultEncryptorService = 'fp_doctrine_encrypt.encryptor.default';
    private static $supportedDrivers = array('orm', 'odm');
    
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder() {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('fp_doctrine_encrypt');
        
        // Grammar of config tree
        $rootNode
            ->children()
                ->arrayNode('db_driver')
                    ->treatNullLike(self::$supportedDrivers)
                        ->prototype('scalar')
                        ->validate()
                        ->ifNotInArray(self::$supportedDrivers)
                            ->thenInvalid('The driver %s is not supported. Please choose of ' . json_encode(self::$supportedDrivers))
                        ->end()
                    ->end()
                    ->cannotBeOverwritten()
                ->cannotBeEmpty()
                ->end()
                ->scalarNode('secret_key')
                    ->beforeNormalization()
                    ->ifNull()
                        ->thenInvalid('You must specifiy secret_key option')
                    ->end()
                ->end()
                ->scalarNode('system_salt')
                    ->beforeNormalization()
                    ->ifNull()
                        ->thenInvalid('You must specifiy system_salt option')
                    ->end()
                ->end()
                ->scalarNode('encrypted_prefix')
                    ->defaultValue(self::defaultPrefix)
                ->end()
                ->scalarNode('encryptor_service')
                    ->defaultValue(self::defaultEncryptorService)                            
                ->end()                    
            ->end();

        return $treeBuilder;
    }

}
