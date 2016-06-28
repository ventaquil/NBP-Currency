<?php

namespace ventaquil\NBPCurrency;

use DateTime;
use ventaquil\NBPCurrency\Exceptions\MissingDateException;
use ventaquil\NBPCurrency\Exceptions\NotNumericException;
use ventaquil\NBPCurrency\Exceptions\NotValidCodeException;
use ventaquil\NBPCurrency\Exceptions\NotValidDateException;
use ventaquil\NBPCurrency\Exceptions\ZeroValueException;

class Currency
{
    protected $buy;
    protected $code;
    protected $date;
    protected $mid;
    protected $sell;

    public function __construct($code, $buy = null, $sell = null, $mid = null, $date = null)
    {
        $this->validateCode($code);

        $this->code = strtoupper($code);

        $this->validateValue($buy, $mid, $sell);

        foreach (array('buy', 'mid', 'sell') as $variable) {
            $this->{$variable} = ${$variable};
        }

        $this->validateDate($date);

        $this->date = $date;
    }

    public function __get($name)
    {
        return $this->{$name};
    }

    protected function validateCode($code)
    {
        if (empty($code) || !is_string($code) || is_numeric($code) || is_bool($code)) {
            throw new NotValidCodeException('Sent code is not valid');
        }
    }

    protected function validateDate($date)
    {
        if (is_null($date)) {
            foreach (array('buy', 'mid', 'sell') as $attribute) {
                if (!is_null($this->{$attribute})) {
                    throw new MissingDateException('You don\'t set date value');
                }
            }
        } else {
            $dateTime = DateTime::createFromFormat('d-m-Y', $date);

            if (($dateTime === false) || ($dateTime->format('d-m-Y') != $date)) {
                throw new NotValidDateException('Sent date is not in valid format');
            }
        }
    }

    protected function validateValue()
    {
        $arguments = func_get_args();
        foreach ($arguments as $argument) {
            if (is_null($argument)) {
                continue;
            }

            if (!is_numeric($argument) || is_bool($argument)) {
                throw new NotNumericException('Sent argument is not a numeric value');
            } elseif ($argument <= 0) {
                throw new ZeroValueException('Argument is less than or equal to zero');
            }
        }
    }
}
