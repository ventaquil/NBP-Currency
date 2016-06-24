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

        if (is_array($this->currency)) {
            $data = $this->loadManyCurrency();

            foreach ($data as $currency => $d) {
                $data[$currency] = $this->validateDateRange($d, $currency);
            }
        } else {
            $data = $this->loadOneCurrency();

            $data = $this->validateDateRange($data, $this->currency);
        }

        while (count($data) == 1) {
            $key = array_keys($data)[0];

            $data = $data[$key];
        }

        return $data;
    }

    protected function loadManyCurrency()
    {
        $currencies = array();

        foreach ($this->currency as $currency) {
            $currencies[$currency] = $this->loadOneCurrency($currency);
        }

        return $currencies;
    }

    protected function loadOneCurrency($currency = null)
    {
        $buy = $date
             = $mid
             = $sell
             = array();

        if (is_null($currency)) {
            $currency = $this->currency;
        }

        if (is_array($this->date)) {
            $xmlUrl = "http://api.nbp.pl/api/exchangerates/rates/%s/{$currency}/{$this->date[0]}/{$this->date[1]}?format=xml";
        } else {
            $xmlUrl = "http://api.nbp.pl/api/exchangerates/rates/%s/{$currency}/{$this->date}?format=xml";
        }

        if (in_array('mid', $this->read)) {
            $xml = file_get_contents(sprintf($xmlUrl, 'A'));

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
            $xml = file_get_contents(sprintf($xmlUrl, 'C'));

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

    public function read($read)
    {
        if (!is_array($read)) {
            $read = array($read);
        }

        $this->validateReadValues($read);

        $this->read = $read;

        return $this;
    }

    protected function validateDateRange($data, $currency)
    {
        if (is_array($this->date)) {
            $newData = array();

            $from = $this->date[0];
            $fromDate = strtotime($from);

            $i = 0;
            foreach ($data as $object) {
                do {
                    $date = strtotime("-{$i} day", strtotime($object->date));

                    if ($date == $fromDate) {
                        $newData[] = $object;
                    } else {
                        $newData[] = new Currency($currency, null, null, null, date('d-m-Y', $fromDate + ($i * 24 * 60 * 60)));
                    }

                    $i++;
                } while ($date != $fromDate);
            }

            $keys = array_reverse(array_keys($newData));
            if (empty($keys)) {
                $lastDate = $fromDate;
            } else {
                $lastDate = strtotime($newData[$keys[0]]->date);
            }

            $to = $this->date[1];
            $toDate = strtotime($to);

            while ($lastDate < $toDate) {
                $newData[] = new Currency($currency, null, null, null, date('d-m-Y', $lastDate));

                $lastDate = strtotime('+1 day', $lastDate);
            }

            $data = $newData;
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
