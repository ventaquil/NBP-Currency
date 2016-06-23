<?php

require_once(__DIR__ . '/vendor/autoload.php');

use ventaquil\NBPCurrency\NBPCurrency;

$currency = NBPCurrency::getInstance()
                       ->currency(['USD', 'EUR'])
                       ->read(['buy', 'mid'])
                       ->date(['2016-06-10', date('Y-m-d')])
                       ->load();

var_dump($currency);
