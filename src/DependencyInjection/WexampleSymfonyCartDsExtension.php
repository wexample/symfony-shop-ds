<?php

namespace Wexample\SymfonyCartDs\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Wexample\SymfonyHelpers\DependencyInjection\AbstractWexampleSymfonyExtension;

class WexampleSymfonyCartDsExtension extends AbstractWexampleSymfonyExtension
{
    public function load(
        array $configs,
        ContainerBuilder $container
    ): void {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->setParameter('wexample_symfony_cart_ds.checkout.account', $config['checkout']['account']);
        $container->setParameter('wexample_symfony_cart_ds.reminder', $config['reminder']);

        $this->loadConfig(
            __DIR__,
            $container
        );
    }
}
