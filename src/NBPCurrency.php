<?php

namespace ventaquil\NBPCurrency;

use ventaquil\NBPCurrency\CurrencyBuilder;

abstract class NBPCurrency {
    public static function currency($code)
    {
        return self::newCurrencyBuilder()->currency($code);
    }

    public static function date($date)
    {
        return self::newCurrencyBuilder()->date($date);
    }

    private static function newCurrencyBuilder()
    {
        return new CurrencyBuilder();
    }

    public static function read($read)
    {
        if (!is_array($read)) {
            $read = array($read);
        }

        return self::newCurrencyBuilder()->read($read);
    }
};
