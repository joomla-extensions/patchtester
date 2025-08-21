<?php

/**
 * Patch testing component for the Joomla! CMS
 *
 * @copyright  Copyright (C) 2011 - 2012 Ian MacLennan, Copyright (C) 2013 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

namespace Joomla\Component\Patchtester\Administrator\Model;

// phpcs:disable PSR1.Files.SideEffects

use Joomla\CMS\MVC\Model\BaseDatabaseModel;

\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Methods supporting applied pull requests.
 *
 * @since  2.0
 */
class TestsModel extends BaseDatabaseModel
{
    /**
     * Retrieves a list of applied patches
     *
     * @return  array List of applied patches
     *
     * @since   2.0
     */
    public function getAppliedPatches(): array
    {
        $db = $this->getDatabase();
        $db->setQuery($db->createQuery()
                ->select('*')
                ->from($db->quoteName('#__patchtester_tests'))
                ->where($db->quoteName('applied') . ' = 1'));
        return $db->loadObjectList('pull_id');
    }

    /**
     * Truncates the tests table
     *
     * @return  void
     *
     * @since   2.0
     */
    public function truncateTable(): void
    {
        $this->getDatabase()->truncateTable('#__patchtester_tests');
    }
}
