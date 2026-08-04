<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a reservation loses the race for the last unit. Carries a
 * customer-facing message, because this is something a shopper sees at
 * checkout rather than an internal fault.
 */
class InsufficientStockException extends RuntimeException
{
}
