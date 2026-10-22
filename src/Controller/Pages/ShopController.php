<?php

namespace Wexample\SymfonyShopDs\Controller\Pages;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Wexample\SymfonyHelpers\Helper\VariableHelper;
use Wexample\SymfonyLoader\Controller\AbstractPagesController;
use Wexample\SymfonyShop\Repository\ProductRepository;
use Wexample\SymfonyShop\Service\ProductOfferService;
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
        ProductOfferService $offers,
    ): Response {
        $salable = $products->findSalable();

        return $this->renderPage(self::ROUTE_INDEX, [
            'products' => $salable,
            'sold_out' => array_map(static fn ($product) => $offers->isSoldOut($product), $salable),
        ]);
    }

    #[Route(path: '{slug}', name: self::ROUTE_PRODUCT)]
    public function product(
        string $slug,
        ProductRepository $products,
        ProductOfferService $offers,
    ): Response {
        $product = $products->findOneBySlug($slug);

        if (! $product || ! $product->isSalable()) {
            throw $this->createNotFoundException();
        }

        return $this->renderPage(self::ROUTE_PRODUCT, [
            'product' => $product,
            // Each schedule and each variation changing something, with its
            // price and what is left of it: what the buyer sees before
            // choosing.
            'offers' => $offers->getOffers($product),
            'sold_out' => $offers->isSoldOut($product),
        ]);
    }
}
