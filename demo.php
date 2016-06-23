<?php

require_once(__DIR__ . '/vendor/autoload.php');

use ventaquil\NBPCurrency\NBPCurrency;

$currency = NBPCurrency::currency(['USD', 'EUR'])
                       ->read(['buy', 'mid'])
                       ->load();

var_dump($currency);