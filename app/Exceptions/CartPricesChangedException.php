<?php

namespace App\Exceptions;

/**
 * A price in the cart moved after the customer last saw it: the exchange
 * rate changed, or the shop edited the price.
 *
 * A RuntimeException on purpose: every checkout entry point, web and mobile,
 * already turns those into a message for the customer, so an order is never
 * placed at a total they were not shown and no caller has to know about this.
 */
class CartPricesChangedException extends \RuntimeException {}
