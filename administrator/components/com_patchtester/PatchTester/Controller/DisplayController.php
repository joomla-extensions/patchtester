<?php
/**
 * Patch testing component for the Joomla! CMS
 *
 * @copyright  Copyright (C) 2011 - 2012 Ian MacLennan, Copyright (C) 2013 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

namespace PatchTester\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;
use PatchTester\Model\AbstractModel;

/**
 * Default display controller
 *
 * @since  2.0
 */
class DisplayController extends AbstractController
{
	/**
	 * Default ordering value
	 *
	 * @var    string
	 * @since  4.0.0
	 */
	protected $defaultFullOrdering = 'a.pull_id DESC';

	/**
	 * Execute the controller.
	 *
	 * @return  boolean  True on success
	 *
	 * @since   2.0
	 * @throws  \RuntimeException
	 */
	public function execute()
	{
		// Set up variables to build our classes
		$view   = $this->getInput()->getCmd('view', $this->defaultView);
		$format = $this->getInput()->getCmd('format', 'html');

		// Register the layout paths for the view
		$paths = new \SplPriorityQueue;

		// Add the path for template overrides
		$paths->insert(JPATH_THEMES . '/' . $this->getApplication()->getTemplate() . '/html/com_patchtester/' . $view, 2);

		// Add the path for the default layouts
		$paths->insert(dirname(__DIR__) . '/View/' . ucfirst($view) . '/tmpl', 1);

		// Build the class names for the model and view
		$viewClass  = '\\PatchTester\\View\\' . ucfirst($view) . '\\' . ucfirst($view) . ucfirst($format) . 'View';
		$modelClass = '\\PatchTester\\Model\\' . ucfirst($view) . 'Model';

		// Sanity check - Ensure our classes exist
		if (!class_exists($viewClass))
		{
			// Try to use a default view
			$viewClass = '\\PatchTester\\View\\Default' . ucfirst($format) . 'View';

			if (!class_exists($viewClass))
			{
				throw new \RuntimeException(Text::sprintf('COM_PATCHTESTER_ERROR_VIEW_NOT_FOUND', $view, $format), 500);
			}
		}

		if (!class_exists($modelClass))
		{
			throw new \RuntimeException(Text::sprintf('COM_PATCHTESTER_ERROR_MODEL_NOT_FOUND', $modelClass), 500);
		}

		// Initialize the model class now; need to do it before setting the state to get required data from it
		$model = new $modelClass($this->context, null, Factory::getDbo());

		// Initialize the state for the model
		$state = $this->initializeState($model);

		foreach ($state as $key => $value)
		{
			$model->setState($key, $value);
		}

		// Initialize the view class now
		$view = new $viewClass($model, $paths);

		// Echo the rendered view for the application
		echo $view->render();

		// Finished!
		return true;
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
		$state = parent::initializeState($model);
		$app = $this->getApplication();

		// Load the filter state.
		$state->set('filter.search', $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', ''));
		$state->set('filter.applied', $app->getUserStateFromRequest($this->context . '.filter.applied', 'filter_applied', ''));
		$state->set('filter.branch', $app->getUserStateFromRequest($this->context . '.filter.branch', 'filter_branch', ''));
		$state->set('filter.rtc', $app->getUserStateFromRequest($this->context . '.filter.rtc', 'filter_rtc', ''));
		$state->set('filter.npm', $app->getUserStateFromRequest($this->context . '.filter.npm', 'filter_npm', ''));
		$state->set('filter.label', $app->getUserStateFromRequest($this->context . '.filter.label', 'filter_label', ''));

		// Pre-fill the limits.
		$limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->input->get('list_limit', 20), 'uint');
		$state->set('list.limit', $limit);

		$fullOrdering = $app->getUserStateFromRequest($this->context . '.fullorder', 'list_fullordering', $this->defaultFullOrdering);

		$orderingParts = explode(' ', $fullOrdering);

		if (count($orderingParts) !== 2)
		{
			$fullOrdering = $this->defaultFullOrdering;

			$orderingParts = explode(' ', $fullOrdering);
		}

		$state->set('list.fullordering', $fullOrdering);

		// The 2nd part will be considered the direction
		$direction = $orderingParts[array_key_last($orderingParts)];

		if (in_array(strtoupper($direction), ['ASC', 'DESC', '']))
		{
			$state->set('list.direction', $direction);
		}

		// The 1st part will be the ordering
		$ordering = $orderingParts[array_key_first($orderingParts)];

		if (in_array($ordering, $model->getSortFields()))
		{
			$state->set('list.ordering', $ordering);
		}

		$value = $app->getUserStateFromRequest($this->context . '.limitstart', 'limitstart', 0);
		$limitstart = ($limit != 0 ? (floor($value / $limit) * $limit) : 0);
		$state->set('list.start', $limitstart);

		return $state;
	}
}
