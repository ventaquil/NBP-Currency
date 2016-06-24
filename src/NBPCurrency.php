<?php

namespace ventaquil\NBPCurrency;

use ventaquil\NBPCurrency\CurrencyBuilder;
use ventaquil\NBPCurrency\Interfaces\FunctionalityInterface;

final class NBPCurrency implements FunctionalityInterface {
    private static $instance;

    private function __construct() { }

    public function currency($code)
    {
        return self::newCurrencyBuilder()->currency($code);
    }

    public function date($date)
    {
        return self::newCurrencyBuilder()->date($date);
    }

    public static function instance()
    {
        return self::getInstance();
    }

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function newCurrencyBuilder()
    {
        return new CurrencyBuilder();
    }

    public function read($read)
    {
        if (!is_array($read)) {
            $read = array($read);
        }

        return self::newCurrencyBuilder()->read($read);
    }
}
