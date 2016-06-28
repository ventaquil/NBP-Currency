<?php

namespace ventaquil\NBPCurrency\Interfaces;

interface FunctionalityInterface
{
    public function currency($code);

    public function date($date);

    public function read($read);
}
