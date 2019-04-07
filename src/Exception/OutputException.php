<?php
/**
 * Dej exception files.
 *
 * @author Mohammad Amin Chitgarha <machitgarha@outlook.com>
 * @see https://github.com/MAChitgarha/Dej
 */

namespace Dej\Exception;

/**
 * Exceptions that should be caught and printed.
 */
class OutputException extends Exception
{
    /**
     * Construct a new exception, just like normal exceptions.
     *
     * @param string $message
     * @param integer $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $message = "", int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
