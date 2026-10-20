<?php

namespace Wexample\SymfonyShopDs\Normalizer;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Wexample\SymfonyHelpers\Interface\HeadStructuredDataProviderInterface;
use Wexample\SymfonyMoney\Helper\MoneyHelper;
use Wexample\SymfonyShop\Class\ProductOffer;
use Wexample\SymfonyShop\Entity\Product;
use Wexample\SymfonyShop\Enum\ProductOfferAvailability;
use Wexample\SymfonyShop\Service\ProductOfferService;
use Wexample\SymfonyShopDs\Controller\Pages\ShopController;

/**
 * A product its page is about, as the engines read it: a Product and what it
 * offers — the same offers the page lists (ProductOfferService), each with
 * its price, what is left and, for a schedule, until when it is sold. A free
 * amount has no price to tell: such a product is left out.
 */
final class ProductSchemaOrgNormalizer implements NormalizerInterface
{
    private const string SCHEMA_ORG = 'https://schema.org/';

    private const array AVAILABILITY = [
        ProductOfferAvailability::Available->value => 'InStock',
        ProductOfferAvailability::Limited->value => 'LimitedAvailability',
        ProductOfferAvailability::SoldOut->value => 'SoldOut',
    ];

    public function __construct(
        private readonly ProductOfferService $offers,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function getSupportedTypes(?string $format): array
    {
        return [Product::class => true];
    }

    public function supportsNormalization(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): bool {
        return HeadStructuredDataProviderInterface::FORMAT_SCHEMA_ORG === $format && $data instanceof Product;
    }

    /**
     * @param Product $data
     */
    public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): ?array {
        if (! $data->isSalable() || $data->isFreeAmount()) {
            return null;
        }

        $url = $this->urlGenerator->generate(
            'shop_'.ShopController::ROUTE_PRODUCT,
            ['slug' => $data->getSlug()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return array_filter([
            '@type' => 'Product',
            'name' => $data->getTitle(),
            'description' => $data->getDescription(),
            'offers' => array_map(
                fn (ProductOffer $offer): array => $this->describeOffer($offer, $url),
                $this->offers->getOffers($data)
            ),
        ]);
    }

    private function describeOffer(ProductOffer $offer, string $url): array
    {
        $schedule = $offer->schedule;
        $variation = $offer->variation;

        return array_filter([
            '@type' => 'Offer',
            'name' => implode(' · ', array_filter([
                $schedule?->getLabel(),
                $variation ? $variation->getName().' — '.$variation->getValue() : null,
            ])) ?: null,
            'price' => MoneyHelper::toDecimal($offer->priceFinal, $offer->currencyCode),
            'priceCurrency' => $offer->currencyCode,
            'availability' => self::SCHEMA_ORG.self::AVAILABILITY[$offer->availability->value],
            // A schedule is sold until it is over.
            'validThrough' => ($schedule?->getDateEnd() ?? $schedule?->getDateStart())?->format(\DateTimeInterface::ATOM),
            'url' => $url,
        ], static fn (mixed $value): bool => null !== $value);
    }
}
