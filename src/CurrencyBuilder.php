<?php

namespace ventaquil\NBPCurrency;

use DateTime;
use SimpleXMLElement;
use UnexpectedValueException;
use ventaquil\NBPCurrency\Currency;
use ventaquil\NBPCurrency\Exceptions\NotValidDateException;
use ventaquil\NBPCurrency\Interfaces\FunctionalityInterface;

class CurrencyBuilder implements FunctionalityInterface {
    protected $date;
    protected $currency;
    protected $read;

    public function currency($code)
    {
        $this->currency = $code;

        return $this;
    }

    public function date($date)
    {
        if (is_array($date)) {
            $this->dateArray($date);
        } else {
            $this->dateValue($date);
        }

        return $this;
    }

    protected function dateArray($date)
    {
        $this->validateDateValue($date[0]);
        $this->validateDateValue($date[1]);

        $this->date = array($date[0], $date[1]);
    }

    protected function dateValue($date)
    {
        $this->validateDateValue($date);

        $this->date = $date;
    }

    public function load()
    {
        if (is_array($this->currency)) {
            $currency = $this->loadMany();
        } else {
            $currency = $this->loadOne($this->currency);
        }

        return $currency;
    }

    protected function loadMany()
    {
        $currencies = array();

        foreach ($this->currency as $currency) {
            $currencies[] = $this->loadOne($currency);
        }

        return $currencies;
    }

    protected function loadOne($currency)
    {
        $buy = $date
             = $mid
             = $sell
             = null;

        if (in_array('mid', $this->read)) {
            $xml = file_get_contents("http://api.nbp.pl/api/exchangerates/rates/A/{$currency}/{$this->date}?format=xml");
            $simpleXML = new SimpleXMLElement($xml);

            $mid = $simpleXML->Rates
                             ->Rate
                             ->Mid
                             ->__toString();

            $date = $simpleXML->Rates
                              ->Rate
                              ->EffectiveDate
                              ->__toString();
        }

        $buyIn = in_array('buy', $this->read);
        $sellIn = in_array('sell', $this->read);
        if ($buyIn || $sellIn) {
            $xml = file_get_contents("http://api.nbp.pl/api/exchangerates/rates/C/{$currency}/{$this->date}?format=xml");
            $simpleXML = new SimpleXMLElement($xml);
    
            if ($buyIn) {
                $buy = $simpleXML->Rates
                                 ->Rate
                                 ->Ask
                                 ->__toString();
            }
    
            if ($sellIn) {
                $sell = $simpleXML->Rates
                                  ->Rate
                                  ->Bid
                                  ->__toString();
            }

            $date = $simpleXML->Rates
                              ->Rate
                              ->EffectiveDate
                              ->__toString();
        }

        if (!is_null($date)) {
            $date = date('d-m-Y', strtotime($date));
        }

        return new Currency($currency, $buy, $sell, $mid, $date);
    }

    public function read($read)
    {
        if (!is_array($read)) {
            $read = array($read);
        }

        $this->validateReadValues($read);

        $this->read = $read;

        return $this;
    }

    protected function validateDateValue($date)
    {
        $dateTime = DateTime::createFromFormat('Y-m-d', $date);

        if (($dateTime === false) || ($dateTime->format('Y-m-d') != $date)) {
            throw new NotValidDateException('Sent date is not in valid format');
        }
    }

    protected function validateReadValues($read)
    {
        foreach ($read as $value) {
            if(!in_array(strtolower($value), array('buy', 'mid', 'sell'))) {
                throw new UnexpectedValueException("Value {$value} is not valid");
            }
        }
    }  
}
