<?php

namespace App\Exceptions;

use Exception;

class ExpiredDiscountException extends Exception
{
    protected $discount;

    public function __construct($message = "", $code = 0, \Throwable $previous = null, $discount = null)
    {
        parent::__construct($message, $code, $previous);
        $this->discount = $discount;
    }

    public function getDiscount()
    {
        return $this->discount;
    }
}
