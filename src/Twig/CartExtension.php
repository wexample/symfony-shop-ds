<?php

namespace Wexample\SymfonyCartDs\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Wexample\SymfonyCartDs\Service\CurrentCartService;

class CartExtension extends AbstractExtension
{
    public function __construct(
        private readonly CurrentCartService $currentCart,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            // How many things the visitor's cart holds: what a link to it in
            // a layout shows beside it.
            new TwigFunction('cart_item_count', $this->currentCart->countItems(...)),
        ];
    }
}
