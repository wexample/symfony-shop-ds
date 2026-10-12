<?php

namespace Wexample\SymfonyShopDs\Controller\Pages;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Wexample\SymfonyHelpers\Helper\VariableHelper;
use Wexample\SymfonyLoader\Controller\AbstractPagesController;
use Wexample\SymfonyShop\Repository\ProductRepository;
use Wexample\SymfonyShopDs\Service\ShopService;
use Wexample\SymfonyShopDs\Traits\SymfonyShopDsBundleClassTrait;

/**
 * What is for sale, and each thing with what can be chosen of it — a
 * variation, a schedule, how many are left —, put in the cart from its page.
 * Open to whoever visits: a cart needs no account until it is paid.
 */
#[Route(path: '/shop/', name: 'shop_')]
final class ShopController extends AbstractPagesController
{
    use SymfonyShopDsBundleClassTrait;

    final public const string ROUTE_INDEX = VariableHelper::INDEX;

    final public const string ROUTE_PRODUCT = 'product';

    #[Route(path: '', name: self::ROUTE_INDEX)]
    public function index(
        ProductRepository $products,
        ShopService $shop,
    ): Response {
        $salable = $products->findSalable();

        return $this->renderPage(self::ROUTE_INDEX, [
            'products' => $salable,
            'sold_out' => array_map(static fn ($product) => $shop->isSoldOut($product), $salable),
        ]);
    }

    #[Route(path: '{slug}', name: self::ROUTE_PRODUCT)]
    public function product(
        string $slug,
        ProductRepository $products,
        ShopService $shop,
    ): Response {
        $product = $products->findOneBySlug($slug);

        if (! $product || ! $product->isSalable()) {
            throw $this->createNotFoundException();
        }

        // What is left of each choice, for the buyer to see before choosing:
        // by variation only where the stock is a variation's own.
        $byVariation = $product->getAvailabilities()->exists(static fn ($key, $availability) => null !== $availability->getVariation());
        $left = [];
        foreach ($shop->getSchedules($product) ?: [null] as $schedule) {
            foreach (($byVariation ? $product->getVariations()->toArray() : []) ?: [null] as $variation) {
                $left[] = [
                    'schedule' => $schedule,
                    'variation' => $variation,
                    'remaining' => $shop->getRemaining($product, $schedule, $variation),
                ];
            }
        }

        return $this->renderPage(self::ROUTE_PRODUCT, [
            'product' => $product,
            'left' => array_values(array_filter($left, static fn ($line) => null !== $line['remaining'])),
            'sold_out' => $shop->isSoldOut($product),
        ]);
    }
}
