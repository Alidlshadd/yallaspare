<?php

namespace App\Exceptions;

/**
 * The exchange rate moved a price in the cart after the customer last saw it.
 *
 * A RuntimeException on purpose: every checkout entry point, web and mobile,
 * already turns those into a message for the customer, so an order is never
 * placed at a total they were not shown and no caller has to know about this.
 */
class CartPricesChangedException extends \RuntimeException {}
