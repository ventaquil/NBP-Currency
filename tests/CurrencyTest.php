<?php

namespace ventaquil\NBPCurrency\Tests;

use ventaquil\NBPCurrency\Currency;
use ventaquil\NBPCurrency\Exceptions\MissingDateException;
use ventaquil\NBPCurrency\Exceptions\NotNumericException;
use ventaquil\NBPCurrency\Exceptions\NotValidCodeException;
use ventaquil\NBPCurrency\Exceptions\NotValidDateException;
use ventaquil\NBPCurrency\Exceptions\ZeroValueException;
use PHPUnit_Framework_TestCase;

class CurrencyTest extends PHPUnit_Framework_TestCase {
    public function testConstructor()
    {
        try {
            $exception = false;

            $currency = new Currency('PLN');
        } catch (NotNumericException $e) {
            $exception = true;
        } catch (ZeroValueException $e) {
                $exception = true;
        } finally {
            $this->assertFalse($exception);
        }

        foreach (array('', null, 12.2, '1', true, array(), array(1, 2)) as $codeToCheck) {
            try {
                $exception = false;

                $currency = new Currency($codeToCheck);
            } catch (NotValidCodeException $e) {
                $exception = true;
            } finally {
                $this->assertTrue($exception);
            }
        }
    }

    public function testCodeValue()
    {
        foreach (array('pln', 'PLN', 'uSd', 'EuR') as $codeToCheck) {
            $currency = new Currency($codeToCheck);

            $this->assertEquals(strtoupper($codeToCheck), $currency->code);
        }
    }

    public function testCurrencyValues()
    {
        foreach (array(1, 1.23, 5893, null) as $currencyToCheck) {
            try {
                $exception = false;

                $currency = new Currency('PLN', $currencyToCheck, null, null, date('d-m-Y'));

                $this->assertEquals($currencyToCheck, $currency->buy);
            } catch (NotNumericException $e) {
                $exception = true;
            } catch (ZeroValueException $e) {
                $exception = true;
            } finally {
                $this->assertFalse($exception);
            }

            try {
                $exception = false;

                $currency = new Currency('PLN', null, $currencyToCheck, null, date('d-m-Y'));

                $this->assertEquals($currencyToCheck, $currency->sell);
            } catch (NotNumericException $e) {
                $exception = true;
            } catch (ZeroValueException $e) {
                $exception = true;
            } finally {
                $this->assertFalse($exception);
            }

            try {
                $exception = false;

                $currency = new Currency('PLN', null, null, $currencyToCheck, date('d-m-Y'));

                $this->assertEquals($currencyToCheck, $currency->mid);
            } catch (NotNumericException $e) {
                $exception = true;
            } catch (ZeroValueException $e) {
                $exception = true;
            } finally {
                $this->assertFalse($exception);
            }
        }

        foreach (array(-1, -3.14, 'a', true, array()) as $currencyToCheck) {
            try {
                $exception = false;

                $currency = new Currency('PLN', $currencyToCheck, null, null, date('d-m-Y'));
            } catch (NotNumericException $e) {
                $exception = true;
            } catch (ZeroValueException $e) {
                $exception = true;
            } finally {
                $this->assertTrue($exception);
            }

            try {
                $exception = false;

                $currency = new Currency('PLN', null, $currencyToCheck, null, date('d-m-Y'));
            } catch (NotNumericException $e) {
                $exception = true;
            } catch (ZeroValueException $e) {
                $exception = true;
            } finally {
                $this->assertTrue($exception);
            }

            try {
                $exception = false;

                $currency = new Currency('PLN', null, null, $currencyToCheck, date('d-m-Y'));
            } catch (NotNumericException $e) {
                $exception = true;
            } catch (ZeroValueException $e) {
                $exception = true;
            } finally {
                $this->assertTrue($exception);
            }
        }
    }

    public function testDateValues()
    {
        try {
            $exception = false;

            $currency = new Currency('PLN');
        } catch (MissingDateException $e) {
            $exception = true;
        } catch (NotValidDateException $e) {
            $exception = true;
        } finally {
            $this->assertFalse($exception);
        }

        try {
            $exception = false;

            $currency = new Currency('PLN', null, null, 10, date('d-m-Y'));
        } catch (MissingDateException $e) {
            $exception = true;
        } catch (NotValidDateException $e) {
            $exception = true;
        } finally {
            $this->assertFalse($exception);
        }

        foreach (array(date('d.m.Y'), date('d/m/Y'), date('m-d-Y'), date('H:i:s'), true, false, 12, 'd-m-Y', '34' . date('-m-Y')) as $dateToCheck) {
            try {
                $exception = false;

                $currency = new Currency('PLN', null, null, null, $dateToCheck);
            } catch (NotValidDateException $e) {
                $exception = true;
            } finally {
                $this->assertTrue($exception);
            }
        }

        try {
            $exception = false;

            $currency = new Currency('PLN', 1);
        } catch (MissingDateException $e) {
            $exception = true;
        } finally {
            $this->assertTrue($exception);
        }
    }
};
