<?php

/**
 * Patch testing component for the Joomla! CMS
 *
 * @copyright  Copyright (C) 2011 - 2012 Ian MacLennan, Copyright (C) 2013 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

namespace Joomla\Component\Patchtester\Administrator\View\Pulls;

use Exception;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\Patchtester\Administrator\Helper\TrackerHelper;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * View class for a list of pull requests.
 *
 * @since  2.0
 *
 * @property-read  \Joomla\Component\Patchtester\Administrator\Model\PullsModel $model  The model object.
 */
class HtmlView extends BaseHtmlView
{
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
     * @var Registry
     * @since 4.4.0
     */
    protected $settings;

    /**
     * Method to render the view.
     *
     * @return  void
     *
     * @throws  Exception
     * @since   2.0.0
     */
    public function display($tpl = null): void
    {
        if (!extension_loaded('openssl')) {
            $this->envErrors[] = Text::_('COM_PATCHTESTER_REQUIREMENT_OPENSSL');
        }

        if (!in_array('https', stream_get_wrappers(), true)) {
            $this->envErrors[] = Text::_('COM_PATCHTESTER_REQUIREMENT_HTTPS');
        }

        if (!count($this->envErrors)) {
            $model               = $this->getModel();
            $this->state         = $model->getState();
            $this->items         = $model->getItems();
            $this->pagination    = $model->getPagination();
            $this->filterForm    = $model->getFilterForm();
            $this->activeFilters = $model->getActiveFilters();
            $this->settings      = ComponentHelper::getParams('com_patchtester');
            $this->trackerAlias  = TrackerHelper::getTrackerAlias(
                $this->state->get('github_user'),
                $this->state->get('github_repo')
            );
        }

        if (count($this->envErrors)) {
            $this->setLayout('errors');
        }

        $this->addToolbar();
        Text::script('COM_PATCHTESTER_CONFIRM_RESET');

        parent::display($tpl);
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
        if (!count($this->envErrors)) {
            $toolbar = $this->getDocument()->getToolbar();
            $toolbar->appendButton('Popup', 'sync', 'COM_PATCHTESTER_TOOLBAR_FETCH_DATA', 'index.php?option=com_patchtester&view=fetch&task=fetch&tmpl=component', 500, 210, 0, 0, 'window.parent.location.reload()', Text::_('COM_PATCHTESTER_HEADING_FETCH_DATA'));
            $toolbar->appendButton('Standard', 'expired', 'COM_PATCHTESTER_TOOLBAR_RESET', 'reset.reset', false);
        }

        ToolbarHelper::preferences('com_patchtester');
    }
}
