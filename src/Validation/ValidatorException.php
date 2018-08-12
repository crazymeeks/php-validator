<?php

namespace Crazymeeks\Validation;

use Exception;

class ValidatorException extends Exception
{

    /**
     * Constructor
     *
     * @param string $message
     * @param integer $code
     * @param \Exception $previous
     * 
     * 
     */
    public function __construct($message, $code = 500, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function validator_not_found($rule, $code = 500, Exception $previous = null)
    {
        $message = sprintf("The validation {%s} not found", $rule);

        return new static($message, $code, $previous);
    }
}