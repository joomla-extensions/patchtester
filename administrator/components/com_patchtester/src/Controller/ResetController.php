<?php

/**
 * Patch testing component for the Joomla! CMS
 *
 * @copyright  Copyright (C) 2011 - 2012 Ian MacLennan, Copyright (C) 2013 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

namespace Joomla\Component\Patchtester\Administrator\Controller;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\Component\Patchtester\Administrator\Model\PullModel;
use Joomla\Component\Patchtester\Administrator\Model\PullsModel;
use Joomla\Component\Patchtester\Administrator\Model\TestsModel;
use Joomla\Filesystem\File;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Controller class to reset the system state
 *
 * @since  2.0
 */
class ResetController extends BaseController
{
    /**
     * Execute the controller.
     *
     * @return  void  Redirects the application
     *
     * @since   2.0
     */
    public function execute($task): void
    {
        try {
            $hasErrors     = false;
            $revertErrored = false;
            $mvcFactory = Factory::getApplication()->bootComponent('com_patchtester')->getMVCFactory();
            /** @var PullModel $pullModel */
            $pullModel  = $mvcFactory->createModel('Pull', 'Administrator', ['ignore_request' => true]);
            /** @var PullsModel $pullModel */
            $pullsModel = $mvcFactory->createModel('Pulls', 'Administrator', ['ignore_request' => true]);
            /** @var TestsModel $pullModel */
            $testsModel = $mvcFactory->createModel('Tests', 'Administrator', ['ignore_request' => true]);

            // Check the applied patches in the database first
            $appliedPatches = $testsModel->getAppliedPatches();
            $params = ComponentHelper::getParams('com_patchtester');
            // Decide based on repository settings whether patch will be applied through Github or CIServer
            if ((bool) $params->get('ci_switch', 1)) {
                // Let's try to cleanly revert all applied patches with ci
                foreach ($appliedPatches as $patch) {
                    try {
                        $pullModel->revertWithCIServer($patch->id);
                    } catch (\RuntimeException $e) {
                        $revertErrored = true;
                    }
                }
            } else {
            // Let's try to cleanly revert all applied patches
                foreach ($appliedPatches as $patch) {
                    try {
                        $pullModel->revertWithGitHub($patch->id);
                    } catch (\RuntimeException $e) {
                        $revertErrored = true;
                    }
                }
            }

            // If we errored out reverting patches, we'll need to truncate the table
            if ($revertErrored) {
                try {
                    $testsModel->truncateTable();
                } catch (\RuntimeException $e) {
                    $hasErrors = true;

                    $this->app->enqueueMessage(
                        Text::sprintf('COM_PATCHTESTER_ERROR_TRUNCATING_PULLS_TABLE', $e->getMessage()),
                        'error'
                    );
                }
            }

            // Now truncate the pulls table
            try {
                $pullsModel->truncateTable();
            } catch (\RuntimeException $e) {
                $hasErrors = true;

                $this->app->enqueueMessage(
                    Text::sprintf('COM_PATCHTESTER_ERROR_TRUNCATING_TESTS_TABLE', $e->getMessage()),
                    'error'
                );
            }

            // Check the backups directory to see if any .txt files remain; clear them if so
            $backups = Folder::files(JPATH_COMPONENT . '/backups', '.txt');

            if (count($backups)) {
                foreach ($backups as $file) {
                    if (!File::delete(JPATH_COMPONENT . '/backups/' . $file)) {
                        $this->app->enqueueMessage(
                            Text::sprintf('COM_PATCHTESTER_ERROR_CANNOT_DELETE_FILE', JPATH_COMPONENT . '/backups/' . $file),
                            'error'
                        );
                        $hasErrors = true;
                    }
                }
            }

            // Processing completed, inform the user of a success or fail
            if ($hasErrors) {
                $msg = Text::sprintf(
                    'COM_PATCHTESTER_RESET_HAS_ERRORS',
                    JPATH_COMPONENT . '/backups',
                    Factory::getApplication()->get('DatabaseDriver')->replacePrefix('#__patchtester_tests')
                );
                $type = 'warning';
            } else {
                $msg  = Text::_('COM_PATCHTESTER_RESET_OK');
                $type = 'notice';
            }
        } catch (\Exception $exception) {
            $msg  = $exception->getMessage();
            $type = 'error';
        }

        $this->app->enqueueMessage($msg, $type);
        $this->app->redirect(Route::_('index.php?option=com_patchtester', false));
    }
}
