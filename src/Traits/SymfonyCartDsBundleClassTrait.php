<?php

namespace Wexample\SymfonyCartDs\Traits;

use Wexample\SymfonyCartDs\WexampleSymfonyCartDsBundle;
use Wexample\SymfonyHelpers\Traits\BundleClassTrait;

trait SymfonyCartDsBundleClassTrait
{
    use BundleClassTrait;

    public static function getBundleClassName(): string
    {
        return WexampleSymfonyCartDsBundle::class;
    }
}
