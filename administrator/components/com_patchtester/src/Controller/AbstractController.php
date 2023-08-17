<?php

/**
 * Patch testing component for the Joomla! CMS
 *
 * @copyright  Copyright (C) 2011 - 2012 Ian MacLennan, Copyright (C) 2013 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

namespace Joomla\Component\Patchtester\Administrator\Controller;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Component\Patchtester\Administrator\Model\AbstractModel;
use Joomla\Input\Input;
use Joomla\Registry\Registry;

/**
 * Base controller for the patch testing component
 *
 * @since  2.0
 */
abstract class AbstractController extends BaseController
{
    /**
     * The active application
     *
     * @var    CMSApplication
     * @since  4.0.0
     */
    protected $app;
/**
     * The object context
     *
     * @var    string
     * @since  2.0
     */
    protected $context;
/**
     * The default view to display
     *
     * @var    string
     * @since  2.0
     */
    protected $defaultView = 'pulls';
/**
     * Instantiate the controller
     *
     * @param   CMSApplication  $app  The application object.
     *
     * @since   2.0
     */
    public function __construct($config = array(), MVCFactoryInterface $factory = null, ?CMSApplication $app = null, ?Input $input = null)
    {
        $this->app = $app;
// Set the context for the controller
        $this->context = 'com_patchtester.' . $this->getInput()->getCmd('view', $this->defaultView);
    }

    /**
     * Get the application object.
     *
     * @return  CMSApplication
     *
     * @since   4.0.0
     */
    public function getApplication()
    {
        return $this->app;
    }

    /**
     * Get the input object.
     *
     * @return  Input
     *
     * @since   4.0.0
     */
    public function getInput()
    {
        return $this->app->input;
    }

    /**
     * Sets the state for the model object
     *
     * @param   AbstractModel  $model  Model object
     *
     * @return  Registry
     *
     * @since   2.0
     */
    protected function initializeState($model)
    {
        $state = new Registry();
// Load the parameters.
        $params = ComponentHelper::getParams('com_patchtester');
        $state->set('github_user', $params->get('org', 'joomla'));
        $state->set('github_repo', $params->get('repo', 'joomla-cms'));
        return $state;
    }
}
