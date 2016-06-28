<?php

namespace ventaquil\NBPCurrency;

use DateTime;
use SimpleXMLElement;
use UnexpectedValueException;
use ventaquil\NBPCurrency\Currency;
use ventaquil\NBPCurrency\Exceptions\MissingCurrencyException;
use ventaquil\NBPCurrency\Exceptions\NotValidDateException;
use ventaquil\NBPCurrency\Interfaces\FunctionalityInterface;

class CurrencyBuilder implements FunctionalityInterface
{
    protected $date;
    protected $currency;
    protected $read;

    protected function createXMLUrl($currency, $type = null)
    {
        if (!is_null($type)) {
            return sprintf($this->createXMLUrl($currency), $type);
        }

        if (is_array($this->date)) {
            return "http://api.nbp.pl/api/exchangerates/rates/%s/{$currency}/{$this->date[0]}/{$this->date[1]}?format=xml";
        } else {
            return "http://api.nbp.pl/api/exchangerates/rates/%s/{$currency}/{$this->date}?format=xml";
        }
    }

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

        if (!is_array($this->currency)) {
            $this->currency = array($this->currency);
        }

        $currencies = array();
        foreach ($this->currency as $currency) {
            $currencies[$currency] = $this->loadOne($currency);
        }

        if (count($currencies) == 1) {
            return $currencies[array_keys($currencies)[0]];
        }

        return $currencies;
    }

    protected function loadBuy($currency)
    {
        $buy = array();

        if (in_array('buy', $this->read)) {
            $xmlUrl = $this->createXMLUrl($currency, 'C');

            $xml = file_get_contents($xmlUrl);

            $simpleXML = new SimpleXMLElement($xml);

            foreach ($simpleXML->Rates->Rate as $rate) {
                $date = $rate->EffectiveDate
                    ->__toString();

                $buy[$date] = $rate->Ask
                    ->__toString();
            }
        }

        return $buy;
    }

    protected function loadMid($currency)
    {
        $mid = array();

        if (in_array('mid', $this->read)) {
            $xmlUrl = $this->createXMLUrl($currency, 'A');

            $xml = file_get_contents($xmlUrl);

            $simpleXML = new SimpleXMLElement($xml);

            foreach ($simpleXML->Rates->Rate as $rate) {
                $date = $rate->EffectiveDate
                             ->__toString();

                $mid[$date] = $rate->Mid
                                   ->__toString();
            }
        }

        return $mid;
    }

    protected function loadOne($currency)
    {
        $buy = $this->loadBuy($currency);
        $mid = $this->loadMid($currency);
        $sell = $this->loadSell($currency);

        $related = $this->relate($buy, $mid, $sell);

        $dateRange = new DateRange();

        foreach ($related as $date => $record) {
            $dateRange[$date] = new Currency($currency, $record['buy'], $record['sell'], $record['mid'], date('d-m-Y', strtotime($date)));
        }

        $dateRange->validate();

        if ($dateRange->count() == 1) {
            return $dateRange->first();
        }

        return $dateRange;
    }

    protected function loadSell($currency)
    {
        $sell = array();

        if (in_array('sell', $this->read)) {
            $xmlUrl = $this->createXMLUrl($currency, 'C');

            $xml = file_get_contents($xmlUrl);

            $simpleXML = new SimpleXMLElement($xml);

            foreach ($simpleXML->Rates->Rate as $rate) {
                $date = $rate->EffectiveDate
                             ->__toString();

                $sell[$date] = $rate->Bid
                                    ->__toString();
            }
        }

        return $sell;
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

    protected function relate($buy, $mid, $sell)
    {
        $dates = array();

        foreach (array('buy', 'mid', 'sell') as $variable) {
            $keys = array_keys(${$variable});

            foreach ($keys as $key) {
                if (!in_array($key, $dates)) {
                    $dates[] = $key;
                }
            }
        }

        sort($dates);

        $data = array();
        foreach ($dates as $date) {
            $data[$date] = array(
                'buy' => isset($buy[$date]) ? $buy[$date] : null,
                'mid' => isset($mid[$date]) ? $mid[$date] : null,
                'sell' => isset($sell[$date]) ? $sell[$date] : null,
            );
        }

        return $data;
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
            if (!in_array(strtolower($value), array('buy', 'mid', 'sell'))) {
                throw new UnexpectedValueException("Value {$value} is not valid");
            }
        }
    }  
}
