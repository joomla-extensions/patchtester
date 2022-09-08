<?php

/**
 * Patch testing component for the Joomla! CMS
 *
 * @copyright  Copyright (C) 2011 - 2012 Ian MacLennan, Copyright (C) 2013 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

namespace PatchTester\View;

use PatchTester\Model\AbstractModel;

/**
 * Base view for the patch testing component
 *
 * @since  4.0.0
 */
abstract class AbstractView
{
    /**
     * The model object.
     *
     * @var    AbstractModel
     * @since  4.0.0
     */
    protected $model;
/**
     * Method to instantiate the view.
     *
     * @param   AbstractModel  $model  The model object.
     *
     * @since   4.0.0
     */
    public function __construct($model)
    {
        $this->model = $model;
    }

    /**
     * Method to escape output.
     *
     * @param   string  $output  The output to escape.
     *
     * @return  string  The escaped output.
     *
     * @since   4.0.0
     */
    public function escape($output)
    {
        return $output;
    }
}
