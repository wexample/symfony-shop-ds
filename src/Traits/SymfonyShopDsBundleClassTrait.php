<?php

namespace Wexample\SymfonyShopDs\Traits;

use Wexample\SymfonyHelpers\Traits\BundleClassTrait;
use Wexample\SymfonyShopDs\WexampleSymfonyShopDsBundle;

trait SymfonyShopDsBundleClassTrait
{
    use BundleClassTrait;

    public static function getBundleClassName(): string
    {
        return WexampleSymfonyShopDsBundle::class;
    }
}
