<?php

namespace Wexample\SymfonyShopDs\Traits;

use Wexample\SymfonyShopDs\WexampleSymfonyShopDsBundle;
use Wexample\SymfonyHelpers\Traits\BundleClassTrait;

trait SymfonyShopDsBundleClassTrait
{
    use BundleClassTrait;

    public static function getBundleClassName(): string
    {
        return WexampleSymfonyShopDsBundle::class;
    }
}
