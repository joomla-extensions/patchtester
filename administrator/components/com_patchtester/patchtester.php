<?php

/**
 * Patch testing component for the Joomla! CMS
 *
 * @copyright  Copyright (C) 2011 - 2012 Ian MacLennan, Copyright (C) 2013 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
if (!Factory::getUser()->authorise('core.manage', 'com_patchtester')) {
    throw new RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
}

// Application reference
$app = Factory::getApplication();
// Import our Composer autoloader to load the component classes
require_once __DIR__ . '/vendor/autoload.php';
// Build the controller class name based on task
$task = $app->input->getCmd('task', 'display');
// If $task is an empty string, apply our default since JInput might not
if ($task === '') {
    $task = 'display';
}

$class = '\\PatchTester\\Controller\\' . ucfirst(strtolower($task)) . 'Controller';
if (!class_exists($class)) {
    throw new InvalidArgumentException(Text::sprintf('JLIB_APPLICATION_ERROR_INVALID_CONTROLLER_CLASS', $class), 404);
}

// Instantiate and execute the controller
/** @var \PatchTester\Controller\AbstractController $controller */
$controller = new $class($app);
$controller->execute();
