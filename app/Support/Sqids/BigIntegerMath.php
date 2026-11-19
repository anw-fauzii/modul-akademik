<?php

namespace App\Support\Sqids;

use Brick\Math\BigInteger;
use Brick\Math\Exception\MathException;
use Sqids\Math\MathInterface;

/**
 * Adapter aritmetika arbitrary-precision berbasis Brick\Math\BigInteger
 * agar sqids/sqids dapat berjalan mulus walau ekstensi gmp/bcmath tidak terpasang.
 */
class BigIntegerMath implements MathInterface
{
    public function add($a, $b)
    {
        return (string) BigInteger::of($a)->plus(BigInteger::of($b));
    }

    public function multiply($a, $b)
    {
        return (string) BigInteger::of($a)->multipliedBy(BigInteger::of($b));
    }

    public function divide($a, $b)
    {
        return (string) BigInteger::of($a)->quotient(BigInteger::of($b));
    }

    public function mod($n, $d)
    {
        return (string) BigInteger::of($n)->remainder(BigInteger::of($d));
    }

    public function greaterThan($a, $b)
    {
        return BigInteger::of($a)->isGreaterThan(BigInteger::of($b));
    }

    public function intval($a)
    {
        $big = BigInteger::of($a);

        if ($big->isGreaterThan(PHP_INT_MAX)) {
            return PHP_INT_MAX;
        }

        if ($big->isLessThan(PHP_INT_MIN)) {
            return PHP_INT_MIN;
        }

        try {
            return $big->toInt();
        } catch (MathException) {
            return PHP_INT_MAX;
        }
    }

    public function strval($a)
    {
        return (string) $a;
    }

    public function get($a)
    {
        return (string) $a;
    }
}
