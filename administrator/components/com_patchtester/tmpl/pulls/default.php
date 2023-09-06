<?php

/**
 * Patch testing component for the Joomla! CMS
 *
 * @copyright  Copyright (C) 2011 - 2012 Ian MacLennan, Copyright (C) 2013 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\WebAsset\WebAssetManager;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/** @var  \Joomla\Component\Patchtester\Administrator\View\Pulls\HtmlView  $this */

/** @var WebAssetManager $wa */
$wa = $this->document->getWebAssetManager();
$wa->usePreset('com_patchtester.patchtester');

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
?>
<form action="<?php echo Route::_('index.php?option=com_patchtester&view=pulls'); ?>" method="post" name="adminForm" id="adminForm">
 <div class="row">
      <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
              <div id="j-main-container" class="j-main-container">
                    <?php if (empty($this->items)) :
                        ?>
                       <div class="alert alert-info">
                            <span class="fa fa-info-circle" aria-hidden="true"></span><span class="sr-only"><?php echo Text::_('INFO'); ?></span>
                            <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                     </div>
                        <?php
                    else :
                        ?>
                     <table class="table">
                          <caption id="captionTable" class="sr-only">
                                <?php echo Text::_('COM_PATCHTESTER_PULLS_TABLE_CAPTION'); ?>, <?php echo Text::_('JGLOBAL_SORTED_BY'); ?>
                         </caption>
                         <thead>
                                <tr>
                                   <th scope="col" style="width:5%" class="text-center">
                                       <?php echo HTMLHelper::_('searchtools.sort', 'COM_PATCHTESTER_PULL_ID', 'pulls.pull_id', $listDirn, $listOrder); ?>
                                    </th>
                                  <th scope="col" style="min-width:100px">
                                      <?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_TITLE', 'pulls.title', $listDirn, $listOrder); ?>
                                   </th>
                                  <th scope="col" style="width:8%" class="d-none d-md-table-cell text-center">
                                        <?php echo Text::_('COM_PATCHTESTER_BRANCH'); ?>
                                  </th>
                                  <th scope="col" style="width:8%" class="d-none d-md-table-cell text-center">
                                        <?php echo Text::_('COM_PATCHTESTER_READY_TO_COMMIT'); ?>
                                 </th>
                                  <th scope="col" style="width:10%" class="text-center">
                                        <?php echo Text::_('JSTATUS'); ?>
                                   </th>
                                  <th scope="col" style="width:15%" class="text-center">
                                        <?php echo Text::_('COM_PATCHTESTER_TEST_THIS_PATCH'); ?>
                                   </th>
                              </tr>
                          </thead>
                           <tbody>
                                <?php echo $this->loadTemplate('items'); ?>
                            </tbody>
                       </table>
                        <?php
                    endif; ?>

                    <?php echo $this->pagination->getListFooter(); ?>

                   <input type="hidden" name="task" value="" />
                   <input type="hidden" name="boxchecked" value="0" />
                    <input type="hidden" name="pull_id" id="pull_id" value="" />
                    <?php echo HTMLHelper::_('form.token'); ?>
                </div>
         </div>
     </div>
 </div>
</form>
