<?php

/**
 * Patch testing component for the Joomla! CMS
 *
 * @copyright  Copyright (C) 2011 - 2012 Ian MacLennan, Copyright (C) 2013 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

namespace Joomla\Component\Patchtester\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseDriver;
use Joomla\Registry\Registry;

/**
 * Base model for the patch testing component
 *
 * @since  4.0.0
 */
abstract class AbstractModel
{
    /**
     * The database driver.
     *
     * @var    DatabaseDriver
     * @since  4.0.0
     */
    protected $db;
/**
     * The model state.
     *
     * @var    Registry
     * @since  4.0.0
     */
    protected $state;

    /**
     * Instantiate the model.
     *
     * @param   Registry|null        $state  The model state.
     * @param   DatabaseDriver|null  $db     The database adapter.
     *
     * @since   4.0.0
     */
    public function __construct(Registry $state = null, DatabaseDriver $db = null)
    {
        $this->state = $state ?: new Registry();
        $this->db    = $db ?: Factory::getDbo();
    }

    /**
     * Get the database driver.
     *
     * @return  DatabaseDriver
     *
     * @since   4.0.0
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * Get the model state.
     *
     * @return  Registry
     *
     * @since   4.0.0
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Set the database driver.
     *
     * @param   DatabaseDriver  $db  The database driver.
     *
     * @return  void
     *
     * @since   4.0.0
     */
    public function setDb(DatabaseDriver $db)
    {
        $this->db = $db;
    }

    /**
     * Set the model state.
     *
     * @param   Registry  $state  The state object.
     *
     * @return  void
     *
     * @since   4.0.0
     */
    public function setState(Registry $state)
    {
        $this->state = $state;
    }
}
