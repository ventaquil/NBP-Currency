<?php

namespace ventaquil\NBPCurrency\Tests;

use UnexpectedValueException;
use ventaquil\NBPCurrency\CurrencyBuilder;
use ventaquil\NBPCurrency\Exceptions\NotValidDateException;
use ventaquil\NBPCurrency\Interfaces\FunctionalityInterface;
use PHPUnit_Framework_TestCase;

class CurrencyBuilderTest extends PHPUnit_Framework_TestCase
{
    public function testImplementedMethods()
    {
        $CurrencyBuilderMethods = get_class_methods(CurrencyBuilder::class);

        foreach (get_class_methods(FunctionalityInterface::class) as $method) {
            $this->assertContains($method, $CurrencyBuilderMethods);
        }
    }

    public function testDate()
    {
        $currencyBuilder = new CurrencyBuilder();

        try {
            $exception = false;

            $currencyBuilder->date(date('Y-m-d'));
        } catch (NotValidDateException $e) {
            $exception = true;
        } finally {
            $this->assertFalse($exception);
        }

        try {
            $exception = false;

            $currencyBuilder->date(date('Y-m-d'), date('Y-m-d'));
        } catch (NotValidDateException $e) {
            $exception = true;
        } finally {
            $this->assertFalse($exception);
        }

        foreach (array('d-m-Y', 'm-d-Y', 'd.m.Y', 'd-m-y', 'd/m/Y', 'm/d/Y', 'Y-d-m', 'Y.m.d', 'y-m-d', 'Y/m/d') as $date) {
            try {
                $exception = false;

                $currencyBuilder->date(date($date));
            } catch (NotValidDateException $e) {
                $exception = true;
            } finally {
                $this->assertTrue($exception);
            }
        }

        foreach (array('d-m-Y', 'm-d-Y', 'd.m.Y', 'd-m-y', 'd/m/Y', 'm/d/Y', 'Y-d-m', 'Y.m.d', 'y-m-d', 'Y/m/d') as $date) {
            try {
                $exception = false;

                $currencyBuilder->date(date($date), date($date));
            } catch (NotValidDateException $e) {
                $exception = true;
            } finally {
                $this->assertTrue($exception);
            }
        }

        foreach (array('abc', 'test', 12, true, 2.0) as $string) {
            try {
                $exception = false;

                $currencyBuilder->date($string);
            } catch (NotValidDateException $e) {
                $exception = true;
            } finally {
                $this->assertTrue($exception);
            }
        }

        foreach (array('abc', 'test', 12, true, 2.0) as $string) {
            try {
                $exception = false;

                $currencyBuilder->date($string, $string);
            } catch (NotValidDateException $e) {
                $exception = true;
            } finally {
                $this->assertTrue($exception);
            }
        }
    }

    public function testRead()
    {
        $currencyBuilder = new CurrencyBuilder();

        foreach (array('buy', 'mid', 'sell') as $option) {
            try {
                $exception = false;

                $currencyBuilder->read($option);
            } catch (UnexpectedValueException $e) {
                $exception = true;
            } finally {
                $this->assertFalse($exception);
            }
        }

        foreach (array('baca', 'Nie', 0, 1.1, false) as $string) {
            try {
                $exception = false;

                $currencyBuilder->read($string);
            } catch (UnexpectedValueException $e) {
                $exception = true;
            } finally {
                $this->assertTrue($exception);
            }
        }
    }
}
