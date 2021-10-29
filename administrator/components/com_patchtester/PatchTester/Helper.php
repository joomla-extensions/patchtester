<?php
/**
 * Patch testing component for the Joomla! CMS
 *
 * @copyright  Copyright (C) 2011 - 2012 Ian MacLennan, Copyright (C) 2013 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

namespace PatchTester;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;
use PatchTester\GitHub\GitHub;

/**
 * Helper class for the patch tester component
 *
 * @since  2.0
 */
abstract class Helper
{
	/**
	 * Initializes the GitHub object
	 *
	 * @return  GitHub
	 *
	 * @since   2.0
	 */
	public static function initializeGithub()
	{
		$params = ComponentHelper::getParams('com_patchtester');

		$options = new Registry;

		// Set a user agent for the request
		$options->set('userAgent', 'PatchTester/3.0');

		// Set the default timeout to 120 seconds
		$options->set('timeout', 120);

		// Set the API URL
		$options->set('api.url', 'https://api.github.com');

		// If an API token is set in the params, use it for authentication
		if ($params->get('gh_token', ''))
		{
			$options->set('headers', ['Authorization' => 'token ' . $params->get('gh_token', '')]);
		}
		// Display a message about the lowered API limit without credentials
		else
		{
			Factory::getApplication()->enqueueMessage(Text::_('COM_PATCHTESTER_NO_CREDENTIALS'), 'notice');
		}

		return new GitHub($options);
	}

	/**
	 * Initializes the CI Settings
	 *
	 * @return  Registry
	 *
	 * @since   3.0
	 */
	public static function initializeCISettings()
	{
		$params = ComponentHelper::getParams('com_patchtester');

		$options = new Registry;

		// Set CI server address for the request
		$options->set('server.url', $params->get('ci_server', 'https://ci.joomla.org:444'));

		// Set name of the zip archive
		$options->set('zip.name', 'build.zip');
		$options->set('zip.log.name', 'deleted_files.log');

		// Set temp archive for extracting and downloading files
		$options->set('folder.temp', Factory::getConfig()->get('tmp_path'));
		$options->set('folder.backups', JPATH_COMPONENT . '/backups');

		// Set full url for addressing the file
		$options->set('zip.url', $options->get('server.url') . '/artifacts/joomla/joomla-cms/4.0-dev/%s/patchtester/' . $options->get('zip.name'));

		return $options;
	}
}
