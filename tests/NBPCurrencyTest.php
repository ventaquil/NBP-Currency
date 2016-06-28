<?php

namespace ventaquil\NBPCurrency\Tests;

use ventaquil\NBPCurrency\Interfaces\FunctionalityInterface;
use ventaquil\NBPCurrency\NBPCurrency;
use PHPUnit_Framework_TestCase;

class NBPCurrencyTest extends PHPUnit_Framework_TestCase
{
    public function testImplementedMethods()
    {
        $NBPCurrencyMethods = get_class_methods(NBPCurrency::class);

        foreach (get_class_methods(FunctionalityInterface::class) as $method) {
            $this->assertContains($method, $NBPCurrencyMethods);
        }
    }
}
