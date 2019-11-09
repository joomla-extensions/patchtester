<?php
/**
 * Patch testing component for the Joomla! CMS
 *
 * @copyright  Copyright (C) 2011 - 2012 Ian MacLennan, Copyright (C) 2013 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

namespace PatchTester\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Filesystem\File;
use PatchTester\Model\PullModel;
use PatchTester\Model\PullsModel;
use PatchTester\Model\TestsModel;

/**
 * Controller class to reset the system state
 *
 * @since  2.0
 */
class ResetController extends AbstractController
{
	/**
	 * Execute the controller.
	 *
	 * @return  void  Redirects the application
	 *
	 * @since   2.0
	 */
	public function execute()
	{
		try
		{
			$hasErrors     = false;
			$revertErrored = false;

			$pullModel  = new PullModel(null, Factory::getDbo());
			$pullsModel = new PullsModel($this->context, null, Factory::getDbo());
			$testsModel = new TestsModel(null, Factory::getDbo());

			// Check the applied patches in the database first
			$appliedPatches = $pullModel->getPatchesDividedInProcs();

			if (count($appliedPatches['git']))
			{
				// Let's try to cleanly revert all applied patches
				foreach ($appliedPatches['git'] as $patch)
				{
					try
					{
						$pullModel->revertWithGitHub($patch->id);
					}
					catch (\RuntimeException $e)
					{
						$revertErrored = true;
					}
				}
			}

			if (count($appliedPatches['ci']))
			{
				// Let's try to cleanly revert all applied patches with ci
				foreach ($appliedPatches['ci'] as $patch)
				{
					try
					{
						$pullModel->revertWithCIServer($patch->insert_id);
					}
					catch (\RuntimeException $e)
					{
						$revertErrored = true;
					}
				}
			}

			// If we errored out reverting patches, we'll need to truncate the table
			if ($revertErrored)
			{
				try
				{
					$testsModel->truncateTable();
				}
				catch (\RuntimeException $e)
				{
					$hasErrors = true;

					$this->getApplication()->enqueueMessage(
						Text::sprintf('COM_PATCHTESTER_ERROR_TRUNCATING_PULLS_TABLE', $e->getMessage()), 'error'
					);
				}
			}

			// Now truncate the pulls table
			try
			{
				$pullsModel->truncateTable();
			}
			catch (\RuntimeException $e)
			{
				$hasErrors = true;

				$this->getApplication()->enqueueMessage(
					Text::sprintf('COM_PATCHTESTER_ERROR_TRUNCATING_TESTS_TABLE', $e->getMessage()), 'error'
				);
			}

			// Check the backups directory to see if any .txt files remain; clear them if so
			$backups = Folder::files(JPATH_COMPONENT . '/backups', '.txt');

			if (count($backups))
			{
				foreach ($backups as $file)
				{
					if (!File::delete(JPATH_COMPONENT . '/backups/' . $file))
					{
						$this->getApplication()->enqueueMessage(
							Text::sprintf('COM_PATCHTESTER_ERROR_CANNOT_DELETE_FILE', JPATH_COMPONENT . '/backups/' . $file), 'error'
						);

						$hasErrors = true;
					}
				}
			}

			// Processing completed, inform the user of a success or fail
			if ($hasErrors)
			{
				$msg = Text::sprintf(
					'COM_PATCHTESTER_RESET_HAS_ERRORS', JPATH_COMPONENT . '/backups', Factory::getDbo()->replacePrefix('#__patchtester_tests')
				);
				$type = 'warning';
			}
			else
			{
				$msg  = Text::_('COM_PATCHTESTER_RESET_OK');
				$type = 'notice';
			}
		}
		catch (\Exception $e)
		{
			$msg  = $e->getMessage();
			$type = 'error';
		}

		$this->getApplication()->enqueueMessage($msg, $type);
		$this->getApplication()->redirect(Route::_('index.php?option=com_patchtester', false));
	}
}
