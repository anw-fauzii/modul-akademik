<?php

namespace App\Support\Sqids;

use Sqids\Math\MathInterface;
use Sqids\Sqids;

/**
 * Instance Sqids yang memakai ekstensi gmp/bcmath bila tersedia
 * dan jatuh ke BigIntegerMath murni PHP bila tidak tersedia.
 */
class SqidsCodec extends Sqids
{
    protected function getMathExtension(): MathInterface
    {
        if (extension_loaded('gmp') || extension_loaded('bcmath')) {
            return parent::getMathExtension();
        }

        return new BigIntegerMath;
    }
}
