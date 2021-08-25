<?php
/**
 * Patch testing component for the Joomla! CMS
 *
 * @copyright  Copyright (C) 2011 - 2012 Ian MacLennan, Copyright (C) 2013 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

namespace PatchTester\View\Pulls;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Registry\Registry;
use PatchTester\TrackerHelper;
use PatchTester\View\DefaultHtmlView;

/**
 * View class for a list of pull requests.
 *
 * @since  2.0
 *
 * @property-read  \PatchTester\Model\PullsModel  $model  The model object.
 */
class PullsHtmlView extends DefaultHtmlView
{
	/**
	 * Array containing the list of branches
	 *
	 * @var    array
	 * @since  3.0.0
	 */
	protected $branches = [];

	/**
	 * Array containing the list of labels
	 *
	 * @var    array
	 * @since  4.0.0
	 */
	protected $labels = [];

	/**
	 * Array containing environment errors
	 *
	 * @var    array
	 * @since  2.0
	 */
	protected $envErrors = [];

	/**
	 * Array of open pull requests
	 *
	 * @var    array
	 * @since  2.0
	 */
	protected $items;

	/**
	 * Pagination object
	 *
	 * @var    Pagination
	 * @since  2.0
	 */
	protected $pagination;

	/**
	 * The model state
	 *
	 * @var    Registry
	 * @since  2.0
	 */
	protected $state;

	/**
	 * The issue tracker project alias
	 *
	 * @var    string|boolean
	 * @since  2.0
	 */
	protected $trackerAlias;

	/**
	 * Method to render the view.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   2.0
	 */
	public function render()
	{
		if (!extension_loaded('openssl'))
		{
			$this->envErrors[] = Text::_('COM_PATCHTESTER_REQUIREMENT_OPENSSL');
		}

		if (!in_array('https', stream_get_wrappers(), true))
		{
			$this->envErrors[] = Text::_('COM_PATCHTESTER_REQUIREMENT_HTTPS');
		}

		// Only process the data if there are no environment errors
		if (!count($this->envErrors))
		{
			$this->state        = $this->model->getState();
			$this->items        = $this->model->getItems();
			$this->pagination   = $this->model->getPagination();
			$this->trackerAlias = TrackerHelper::getTrackerAlias($this->state->get('github_user'), $this->state->get('github_repo'));
			$this->branches     = $this->model->getBranches();
			$this->labels       = $this->model->getLabels();
		}

		// Change the layout if there are environment errors
		if (count($this->envErrors))
		{
			$this->setLayout('errors');
		}

		$this->addToolbar();

		// Make text strings available in the JavaScript API
		Text::script('COM_PATCHTESTER_CONFIRM_RESET');

		return parent::render();
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	protected function addToolbar(): void
	{
		ToolbarHelper::title(Text::_('COM_PATCHTESTER'), 'patchtester fas fa-save');

		if (!count($this->envErrors))
		{
			$toolbar = Toolbar::getInstance('toolbar');

			$toolbar->appendButton(
				'Popup',
				'sync',
				'COM_PATCHTESTER_TOOLBAR_FETCH_DATA',
				'index.php?option=com_patchtester&view=fetch&tmpl=component',
				500,
				210,
				0,
				0,
				'window.parent.location.reload()',
				Text::_('COM_PATCHTESTER_HEADING_FETCH_DATA')
			);

			// Add a reset button.
			$toolbar->appendButton('Standard', 'expired', 'COM_PATCHTESTER_TOOLBAR_RESET', 'reset', false);
		}

		ToolbarHelper::preferences('com_patchtester');
	}

	/**
	 * Returns an array of values to be used for pagination limits
	 *
	 * @return  array
	 *
	 * @since   4.0.0
	 */
	protected function getLimitOptions()
	{
		return [
			5   => Text::_('J5'),
			10  => Text::_('J10'),
			15  => Text::_('J15'),
			20  => Text::_('J20'),
			25  => Text::_('J25'),
			30  => Text::_('J30'),
			50  => Text::_('J50'),
			100 => Text::_('J100'),
			200 => Text::_('J200'),
			500 => Text::_('J500'),
			0   => Text::_('JALL'),
		];
	}

	/**
	 * Returns an array of fields the table can be sorted by
	 *
	 * @return  array
	 *
	 * @since   2.0
	 */
	protected function getSortFields()
	{
		return [
			'a.title ASC'    => Text::_('JGLOBAL_TITLE_ASC'),
			'a.title DESC'   => Text::_('JGLOBAL_TITLE_DESC'),
			'a.pull_id ASC'  => Text::_('COM_PATCHTESTER_PULL_ID_ASC'),
			'a.pull_id DESC' => Text::_('COM_PATCHTESTER_PULL_ID_DESC'),
		];
	}
}
