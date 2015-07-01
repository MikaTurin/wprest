<?php

namespace WP\Route\Http\Exception;

use Exception;
use WP\Route\Http\Exception as HttpException;

class NotFoundException extends HttpException
{
    /**
     * Constructor
     *
     * @param string     $message
     * @param \Exception $previous
     * @param integer    $code
     */
    public function __construct($message = 'Not Found', Exception $previous = null, $code = 0)
    {
        parent::__construct(404, $message, $previous, [], $code);
    }
}
