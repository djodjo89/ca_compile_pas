<?php

namespace App\Exception;

class MissingParameterException extends \Exception
{
    public function __construct(string $param)
    {
        parent::__construct($param . ' was not provided');
    }
}
