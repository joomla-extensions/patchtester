<?php
/**
 * Patch testing component for the Joomla! CMS
 *
 * @copyright  Copyright (C) 2011 - 2012 Ian MacLennan, Copyright (C) 2013 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

namespace PatchTester\View\Pulls;

use Exception;
use Joomla\CMS\Form\Form;
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
 * @property-read  \PatchTester\Model\PullsModel $model  The model object.
 */
class PullsHtmlView extends DefaultHtmlView
{
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
	 * Form object for search filters
	 *
	 * @var   Form
	 * @since 4.1.0
	 */
	public $filterForm;

	/**
	 * The active search filters
	 *
	 * @var   array
	 * @since 4.1.0
	 */
	public $activeFilters;

	/**
	 * The model state
	 *
	 * @var    Registry
	 * @since  2.0.0
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
	 * @since   2.0.0
	 * @throws  Exception
	 */
	public function render(): string
	{
		if (!extension_loaded('openssl'))
		{
			$this->envErrors[] = Text::_('COM_PATCHTESTER_REQUIREMENT_OPENSSL');
		}

		if (!in_array('https', stream_get_wrappers(), true))
		{
			$this->envErrors[] = Text::_('COM_PATCHTESTER_REQUIREMENT_HTTPS');
		}

		if (!count($this->envErrors))
		{
			$this->state         = $this->model->getState();
			$this->items         = $this->model->getItems();
			$this->pagination    = $this->model->getPagination();
			$this->filterForm    = $this->model->getFilterForm();
			$this->activeFilters = $this->model->getActiveFilters();
			$this->trackerAlias  = TrackerHelper::getTrackerAlias(
				$this->state->get('github_user'),
				$this->state->get('github_repo')
			);
		}

		// Change the layout if there are environment errors
		if (count($this->envErrors))
		{
			$this->setLayout('errors');
		}

		$this->addToolbar();

		Text::script('COM_PATCHTESTER_CONFIRM_RESET');

		return parent::render();
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 * @since   2.0.0
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
}
