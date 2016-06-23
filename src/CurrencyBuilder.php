<?php

namespace ventaquil\NBPCurrency;

use DateTime;
use SimpleXMLElement;
use UnexpectedValueException;
use ventaquil\NBPCurrency\Currency;
use ventaquil\NBPCurrency\Exceptions\MissingCurrencyException;
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
        if (is_null($this->currency)) {
            throw new MissingCurrencyException('You are forgetting something?');
        }

        $currencyIsArray = is_array($this->currency);
        $dateIsArray = is_array($this->date);

        switch (true) {
            case !$currencyIsArray && !$dateIsArray:
                $data = $this->loadOneToOne($this->currency);
                break;
            case !$currencyIsArray && $dateIsArray:
                $data = $this->loadOneToMany($this->currency);
                break;
            case $currencyIsArray && !$dateIsArray:
                $data = $this->loadManyToOne();
                break;
            case $currencyIsArray && $dateIsArray:
                $data = $this->loadManyToMany();
                break;
        }

        return $data;
    }

    protected function loadManyToMany()
    {
        $currencies = array();

        foreach ($this->currency as $currency) {
            $currencies[$currency] = $this->loadOneToMany($currency);
        }

        return $currencies;
    }

    protected function loadManyToOne()
    {
        $currencies = array();

        foreach ($this->currency as $currency) {
            $currencies[$currency] = $this->loadOneToOne($currency);
        }

        return $currencies;
    }

    protected function loadOneToMany($currency)
    {
        $buy = $date
             = $mid
             = $sell
             = array();

        if (in_array('mid', $this->read)) {
            $xml = file_get_contents("http://api.nbp.pl/api/exchangerates/rates/A/{$currency}/{$this->date[0]}/{$this->date[1]}?format=xml");
            $simpleXML = new SimpleXMLElement($xml);

            $i = 0;
            foreach ($simpleXML->Rates->Rate as $rate) {
                $mid[$i] = $rate->Mid
                                ->__toString();

                $date[$i] = $rate->EffectiveDate
                                 ->__toString();

                $i++;
            }
        }

        $buyIn = in_array('buy', $this->read);
        $sellIn = in_array('sell', $this->read);
        if ($buyIn || $sellIn) {
            $xml = file_get_contents("http://api.nbp.pl/api/exchangerates/rates/C/{$currency}/{$this->date[0]}/{$this->date[1]}?format=xml");
            $simpleXML = new SimpleXMLElement($xml);
    
            $i = 0;
            foreach ($simpleXML->Rates->Rate as $rate) {
                if ($buyIn) {
                    $buy[$i] = $rate->Ask
                                    ->__toString();
                }
        
                if ($sellIn) {
                    $sell[$i] = $rate->Bid
                                     ->__toString();
                }

                $date[$i] = $rate->EffectiveDate
                                 ->__toString();
                $i++;
            }
        }

        $currencies = array();
        foreach ($date as $i => $d) {
            if (!is_null($d)) {
                $date[$i] = date('d-m-Y', strtotime($d));
            }

            foreach (array('buy', 'mid', 'sell') as $variable) {
                if (!isset(${$variable}[$i])) {
                    ${$variable}[$i] = null;
                }
            }

            $currencies[] = new Currency($currency, $buy[$i], $sell[$i], $mid[$i], $date[$i]);
        }

        return $currencies;
    }

    protected function loadOneToOne($currency)
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
