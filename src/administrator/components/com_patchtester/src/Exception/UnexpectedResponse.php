<?php

/**
 * Patch testing component for the Joomla! CMS
 *
 * @copyright  Copyright (C) 2011 - 2012 Ian MacLennan, Copyright (C) 2013 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

namespace Joomla\Component\Patchtester\Administrator\Exception;

use DomainException;
use Exception;
use Joomla\Http\Response;

/**
 * Exception representing an unexpected response
 *
 * @since  3.0.0
 */
class UnexpectedResponse extends DomainException
{
    /**
     * The Response object.
     *
     * @var    Response
     * @since  3.0.0
     */
    private $response;

    /**
     * Constructor
     *
     * @param   Response        $response  The Response object.
     * @param   string          $message   The Exception message to throw.
     * @param   int             $code      The Exception code.
     * @param   Exception|null  $previous  The previous exception used for the exception chaining.
     *
     * @since   3.0.0
     */
    public function __construct(
        Response $response,
        $message = '',
        $code = 0,
        Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->response = $response;
    }

    /**
     * Get the Response object.
     *
     * @return  Response
     *
     * @since   3.0.0
     */
    public function getResponse(): Response
    {
        return $this->response;
    }
}
