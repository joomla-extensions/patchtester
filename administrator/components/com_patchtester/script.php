<?php

/**
 * Patch testing component for the Joomla! CMS
 *
 * @copyright  Copyright (C) 2011 - 2012 Ian MacLennan, Copyright (C) 2013 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Installer\Adapter\ComponentAdapter;
use Joomla\CMS\Installer\InstallerScript;
use Joomla\CMS\Language\Text;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Installation class to perform additional changes during install/uninstall/update
 *
 * @since  2.0
 */
class Com_PatchtesterInstallerScript extends InstallerScript
{
    /**
     * Extension script constructor.
     *
     * @since   3.0.0
     */
    public function __construct()
    {
        $this->minimumJoomla = '5.0';
        $this->minimumPhp    = JOOMLA_MINIMUM_PHP;
        $this->deleteFolders = [
            '/administrator/components/com_patchtester/PatchTester',
            '/components/com_patchtester',
        ];
        Factory::getApplication()
            ->getLanguage()
            ->load('com_patchtester.sys', JPATH_ADMINISTRATOR, null, true);
    }

    /**
     * Show the message on install.
     *
     * @param   ComponentAdapter  $parent  The class calling this method
     *
     * @return  void
     *
     * @since   4.0.0
     */
    public function install(ComponentAdapter $parent): void
    {
        ?>
      <h1>
            <?php echo HTMLHelper::_('image', 'media/com_patchtester/images/icon-48-patchtester.png', Text::_('COM_PATCHTESTER')); ?>
            <?php echo Text::_('COM_PATCHTESTER'); ?>
       </h1>
        <p><?php echo Text::_('COM_PATCHTESTER_INSTALL_INSTRUCTIONS'); ?></p>
        <?php
    }

    /**
     * Show the message on install.
     *
     * @param   ComponentAdapter  $parent  The class calling this method
     *
     * @return  void
     *
     * @since   4.0.0
     */
    public function update(ComponentAdapter $parent): void
    {
        ?>
      <h1>
            <?php echo HTMLHelper::_('image', 'media/com_patchtester/images/icon-48-patchtester.png', Text::_('COM_PATCHTESTER')); ?>
            <?php echo Text::_('COM_PATCHTESTER'); ?>
       </h1>
        <p><?php echo Text::_('COM_PATCHTESTER_UPDATE_INSTRUCTIONS'); ?></p>
        <?php
    }


    /**
     * Show the message on install.
     *
     * @param   ComponentAdapter  $parent  The class calling this method
     *
     * @return  void
     *
     * @since   4.0.0
     */
    public function uninstall(ComponentAdapter $parent): void
    {
        ?>
       <h1>
            <?php echo HTMLHelper::_('image', 'media/com_patchtester/images/icon-48-patchtester.png', Text::_('COM_PATCHTESTER')); ?>
     </h1>
        <p><?php echo Text::_('COM_PATCHTESTER_UNINSTALL_THANK_YOU'); ?></p>
        <?php
    }

    /**
     * Function to perform changes during postflight
     *
     * @param   string            $type    The action being performed
     * @param   ComponentAdapter  $parent  The class calling this method
     *
     * @return  void
     *
     * @since   3.0.0
     */
    public function postflight(string $type, ComponentAdapter $parent): void
    {
        $this->removeFiles();
    }
}
