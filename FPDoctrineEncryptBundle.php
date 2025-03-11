<?php

namespace FP\DoctrineEncryptBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use FP\DoctrineEncryptBundle\DependencyInjection\FPDoctrineEncryptExtension;
use FP\DoctrineEncryptBundle\DependencyInjection\Compiler\RegisterServiceCompilerPass;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

class FPDoctrineEncryptBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new RegisterServiceCompilerPass(), PassConfig::TYPE_AFTER_REMOVING);
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return new FPDoctrineEncryptExtension();
    }
}
