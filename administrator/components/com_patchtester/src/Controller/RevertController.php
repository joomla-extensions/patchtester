<?php

/**
 * Patch testing component for the Joomla! CMS
 *
 * @copyright  Copyright (C) 2011 - 2012 Ian MacLennan, Copyright (C) 2013 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

namespace Joomla\Component\Patchtester\Administrator\Controller;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\Component\Patchtester\Administrator\Model\PullModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Controller class to revert patches
 *
 * @since  2.0
 */
class RevertController extends BaseController
{
    /**
     * Execute the controller.
     *
     * @return  void  Redirects the application
     *
     * @since   2.0
     */
    public function execute($task)
    {
        try {
            /** @var PullModel $model */
            $model = $this->app->bootComponent('com_patchtester')->getMVCFactory()->createModel('Pull', 'Administrator', ['ignore_request' => true]);
            $model->revert($this->input->getUint('pull_id'));
            $msg  = Text::_('COM_PATCHTESTER_REVERT_OK');
            $type = 'message';
        } catch (\Exception $e) {
            $msg  = $e->getMessage();
            $type = 'error';
        }

        $this->app->enqueueMessage($msg, $type);
        $this->app->redirect(Route::_('index.php?option=com_patchtester', false));
    }
}
