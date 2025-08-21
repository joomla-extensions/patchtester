<?php

/**
 * Patch testing component for the Joomla! CMS
 *
 * @copyright  Copyright (C) 2011 - 2012 Ian MacLennan, Copyright (C) 2013 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

namespace Joomla\Component\Patchtester\Administrator\Helper;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Component\Patchtester\Administrator\GitHub\GitHub;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

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
     * @throws \Exception
     * @since   2.0
     */
    public static function initializeGithub()
    {
        $params  = ComponentHelper::getParams('com_patchtester');
        $options = new Registry();
        // Set a user agent for the request
        $options->set('userAgent', 'PatchTester/4.0');
        // Set the default timeout to 120 seconds
        $options->set('timeout', 120);
        // Set the API URL
        $options->set('api.url', 'https://api.github.com');
        // If an API token is set in the params, use it for authentication
        if ($params->get('gh_token', '')) {
            $options->set('headers', ['Authorization' => 'token ' . $params->get('gh_token', '')]);
        } else {
            // Display a message about the lowered API limit without credentials
            Factory::getApplication()->enqueueMessage(Text::_('COM_PATCHTESTER_NO_CREDENTIALS'), 'notice');
        }

        return new GitHub($options);
    }
}
