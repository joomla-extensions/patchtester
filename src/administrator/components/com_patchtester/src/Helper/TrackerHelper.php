<?php

/**
 * Patch testing component for the Joomla! CMS
 *
 * @copyright  Copyright (C) 2011 - 2012 Ian MacLennan, Copyright (C) 2013 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

namespace Joomla\Component\Patchtester\Administrator\Helper;

/**
 * Helper class for Joomla's issue tracker integrations
 *
 * @since  2.0
 */
abstract class TrackerHelper
{
    /**
     * Array containing the supported repositories integrated with the Joomla! Issue Tracker
     *
     * @var    array
     * @since  2.0
     */
    private static $projects = [
        'joomla-cms' => [
            'githubUser'   => 'joomla',
            'githubrepo'   => 'joomla-cms',
            'projectAlias' => 'joomla-cms',
        ],
        'patchtester' => [
            'githubUser'   => 'joomla-extensions',
            'githubrepo'   => 'patchtester',
            'projectAlias' => 'patchtester',
        ],
        'weblinks' => [
            'githubUser'   => 'joomla-extensions',
            'githubrepo'   => 'weblinks',
            'projectAlias' => 'weblinks',
        ],
    ];

    /**
     * Get the issue tracker project alias for a GitHub repository
     *
     * @param   string  $githubUser  The owner of the GitHub repository (user or organization)
     * @param   string  $githubRepo  The GitHub repository name
     *
     * @return  string|boolean  The project alias if supported or boolean false
     *
     * @since   2.0
     */
    public static function getTrackerAlias($githubUser, $githubRepo)
    {
        // If the repo isn't even listed, no point in going further
        if (!\array_key_exists($githubRepo, self::$projects)) {
            return false;
        }

        // Now the GitHub user must match the project (we don't support forks, sorry!)
        if (self::$projects[$githubRepo]['githubUser'] !== $githubUser) {
            return false;
        }

        // This project is supported
        return self::$projects[$githubRepo]['projectAlias'];
    }
}
