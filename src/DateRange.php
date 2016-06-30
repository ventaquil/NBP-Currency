<?php

namespace ventaquil\NBPCurrency;

use ArrayAccess;
use Doctrine\Instantiator\Exception\InvalidArgumentException;

class DateRange implements ArrayAccess
{
    private $currencies = array();

    private function checkDate($date)
    {
        return $this->getTimestamp($date) !== false;
    }

    public function count()
    {
        return count($this->currencies);
    }

    public function first()
    {
        $dates = $this->getSortedDates();

        if (!empty($dates)) {
            return $this->currencies[$dates[0]];
        }

        return null;
    }

    public function getSortedDates()
    {
        $dates = array_keys($this->currencies);
        sort($dates, SORT_NUMERIC);

        return $dates;
    }

    private function getTimestamp($date)
    {
        return strtotime($date);
    }

    public function last()
    {
        $dates = array_reverse($this->getSortedDates());

        if (!empty($dates)) {
            return $this->currencies[$dates[0]];
        }

        return null;
    }

    public function offsetExists($offset)
    {
        if ($this->checkDate($offset)) {
            $timestamp = $this->getTimestamp($offset);

            return isset($this->currencies[$timestamp]);
        }

        return false;
    }

    public function offsetGet($offset)
    {
        if ($this->offsetExists($offset)) {
            $timestamp = $this->getTimestamp($offset);

            return $this->currencies[$timestamp];
        }

        return null;
    }

    public function offsetSet($offset, $value)
    {
        if ($this->checkDate($offset) && ($value instanceof Currency)) {
            $timestamp = $this->getTimestamp($offset);

            $this->currencies[$timestamp] = $value;

            return $value;
        }

        throw new InvalidArgumentException('Argument must be instance of \\ventaquil\\Currency');
    }

    public function offsetUnset($offset)
    {
        if ($this->offsetExists($offset)) {
            $timestamp = $this->getTimestamp($offset);

            unset($this->currencies[$timestamp]);

            return true;
        }

        return false;
    }

    public function toArray()
    {
        return $this->currencies;
    }

    public function validate()
    {
        $dates = $this->getSortedDates();

        if (empty($dates)) {
            return;
        }

        $first = $this->first();

        $date = $dates[0]; // Set date as first available date
        $last = $dates[count($dates) - 1];

        while ($date < $last) {
            if (!isset($this->currencies[$date])) {
                $this->currencies[$date] = new Currency($first->code, null, null, null, date('d-m-Y', $date));
            }

            $date += 86400; // 24 * 60 * 60
        }

        uasort($this->currencies, function ($a, $b) {
            $aDate = strtotime($a->date);
            $bDate = strtotime($b->date);

            if ($aDate == $bDate) {
                return 0;
            }

            return $aDate < $bDate ? -1 : 1;
        });
    }
}