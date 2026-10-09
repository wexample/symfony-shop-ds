<?php

namespace Wexample\SymfonyCartDs\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const string ACCOUNT_GUEST = 'guest';

    public const string ACCOUNT_REQUIRED = 'account';

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('wexample_symfony_cart_ds');

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('checkout')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->enumNode('account')
                            ->info('Who buys: `guest`, an email and no account; `account`, the buyer\'s account — signed in when the address has one, created, not activated, when it has none.')
                            ->values([self::ACCOUNT_GUEST, self::ACCOUNT_REQUIRED])
                            ->defaultValue(self::ACCOUNT_GUEST)
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('reminder')
                    ->info('A cart left on the way, written about once to its buyer, with a link bringing it back.')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')->defaultTrue()->end()
                        ->scalarNode('delay')
                            ->info('How long a cart stays untouched before its buyer is written to (ISO 8601 duration).')
                            ->defaultValue('P1D')
                        ->end()
                        ->scalarNode('within')
                            ->info('How long after its last change a cart is still written about, and its link lasts (ISO 8601 duration).')
                            ->defaultValue('P7D')
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
