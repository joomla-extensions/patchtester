<?php

/**
 * Patch testing component for the Joomla! CMS
 *
 * @copyright  Copyright (C) 2011 - 2012 Ian MacLennan, Copyright (C) 2013 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\WebAsset\WebAssetManager;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/** @var WebAssetManager $wa */
$wa = $this->document->getWebAssetManager();
$wa->useScript('com_patchtester.admin-fetcher-modal');

HTMLHelper::_('behavior.core');
//HTMLHelper::_('script', 'com_patchtester/fetcher.js', ['version' => 'auto', 'relative' => true]);
Text::script('COM_PATCHTESTER_FETCH_AN_ERROR_HAS_OCCURRED');

?>

<div id="patchtester-container">
    <h1 id="patchtester-progress-header"><?php echo Text::_('COM_PATCHTESTER_FETCH_INITIALIZING'); ?></h1>
    <p id="patchtester-progress-message"><?php echo Text::_('COM_PATCHTESTER_FETCH_INITIALIZING_DESCRIPTION'); ?></p>
  <div id="progress" class="progress">
       <div id="progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" role="progressbar"></div>
    </div>
    <input id="patchtester-token" type="hidden" name="<?php echo Factory::getApplication()->getSession()->getFormToken(); ?>" value="1" />
</div>
