<?php
/**
 * @package    PatchTester
 *
 * @copyright  Copyright (C) 2011 - 2012 Ian MacLennan, Copyright (C) 2013 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

// Access check.
if (!JFactory::getUser()->authorise('core.manage', 'com_patchtester'))
{
	return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
}

// Application reference
$app = JFactory::getApplication();

// Register the component namespace to the autoloader
JLoader::registerNamespace('PatchTester', __DIR__);

// Build the controller class name based on task
$task = $app->input->getCmd('task', 'display');

// If $task is an empty string, apply our default since JInput might not
if ($task === '')
{
	$task = 'display';
}

$class = '\\PatchTester\\Controller\\' . ucfirst(strtolower($task)) . 'Controller';

// Increase PHP max execution time to 5 minute
ini_set('max_execution_time', 300);

// Instantiate and execute the controller
$controller = new $class($app->input, $app);
$controller->execute();
