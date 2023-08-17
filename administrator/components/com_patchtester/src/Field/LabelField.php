<?php

/**
 * Patch testing component for the Joomla! CMS
 *
 * @copyright  Copyright (C) 2011 - 2012 Ian MacLennan, Copyright (C) 2013 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

namespace Joomla\Component\Patchtester\Administrator\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * List of available banks.
 *
 * @package  PatchTester
 * @since    4.1.0
 */
class LabelField extends ListField
{
    /**
     * Type of field
     *
     * @var    string
     * @since  4.1.0
     */
    protected $type = 'Label';
/**
     * Build a list of available fields.
     *
     * @return  array  List of options
     *
     * @since   4.1.0
     */
    public function getOptions(): array
    {
        $db    = Factory::getContainer()->get('DatabaseDriver');
        $query = $db->getQuery(true);
        $query->select('DISTINCT(' . $db->quoteName('name') . ') AS ' . $db->quoteName('text'))
            ->select($db->quoteName('name', 'value'))
            ->from($db->quoteName('#__patchtester_pulls_labels'))
            ->order($db->quoteName('name') . ' ASC');
        $options = $db->setQuery($query)->loadAssocList();
        return array_merge(parent::getOptions(), $options);
    }
}
