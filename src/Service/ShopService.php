<?php

namespace Wexample\SymfonyShopDs\Service;

use Wexample\SymfonyShop\Entity\Product;
use Wexample\SymfonyShop\Entity\ProductVariation;
use Wexample\SymfonyShop\Entity\Schedule;
use Wexample\SymfonyShop\Service\StockService;

/**
 * What the shop shows of a product beside its price: the schedules it can be
 * bought for, and how many are left — read as the reservation of symfony-shop
 * reads them: every availability of the product matching the schedule (or
 * any) and the variation (or any), the tightest one winning.
 */
class ShopService
{
    public function __construct(
        private readonly StockService $stock,
    ) {
    }

    /**
     * The schedules still to come it can be bought for, soonest first.
     *
     * @return list<Schedule>
     */
    public function getSchedules(Product $product): array
    {
        $schedules = [];

        foreach ($product->getAvailabilities() as $availability) {
            $schedule = $availability->getSchedule();

            if ($schedule && ! $schedule->isPast()) {
                $schedules[spl_object_id($schedule)] = $schedule;
            }
        }

        $schedules = array_values($schedules);
        usort($schedules, static fn (Schedule $a, Schedule $b) => $a->getDateStart() <=> $b->getDateStart());

        return $schedules;
    }

    /**
     * How many are left, or null when nothing limits it.
     */
    public function getRemaining(
        Product $product,
        ?Schedule $schedule = null,
        ?ProductVariation $variation = null,
    ): ?int {
        $remaining = null;

        foreach ($product->getAvailabilities() as $availability) {
            if ($availability->getSchedule() && $availability->getSchedule() !== $schedule) {
                continue;
            }

            if ($availability->getVariation() && $availability->getVariation() !== $variation) {
                continue;
            }

            $available = $this->stock->getAvailable($availability);

            if (null !== $available) {
                $remaining = null === $remaining ? $available : min($remaining, $available);
            }
        }

        return $remaining;
    }

    /**
     * Nothing left to buy, whatever is chosen: every schedule and every
     * variation it is sold with is sold out.
     */
    public function isSoldOut(Product $product): bool
    {
        $schedules = $this->getSchedules($product) ?: [null];
        $variations = $product->getVariations()->toArray() ?: [null];

        foreach ($schedules as $schedule) {
            foreach ($variations as $variation) {
                if (0 !== $this->getRemaining($product, $schedule, $variation)) {
                    return false;
                }
            }
        }

        return true;
    }
}
