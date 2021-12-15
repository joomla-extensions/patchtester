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
use Joomla\CMS\Router\Route;

/** @var  \PatchTester\View\Pulls\PullsHtmlView  $this */

$searchToolsOptions = array(
	'filtersHidden'       => true,
	'filterButton'        => true,
	'defaultLimit'        => Factory::getApplication()->get('list_limit', 20),
	'searchFieldSelector' => '#filter_search',
	'selectorFieldName'   => 'client_id',
	'showSelector'        => false,
	'orderFieldSelector'  => '#list_fullordering',
	'showNoResults'       => false,
	'noResultsText'       => '',
	'formSelector'        => '#adminForm',
);

HTMLHelper::_('behavior.core');
HTMLHelper::_('searchtools.form', '#adminForm', $searchToolsOptions);
HTMLHelper::_('stylesheet', 'com_patchtester/octicons.css', ['version' => '3.5.0', 'relative' => true]);
HTMLHelper::_('script', 'com_patchtester/patchtester.js', ['version' => 'auto', 'relative' => true]);

$listOrder     = $this->escape($this->state->get('list.fullordering', 'a.pull_id DESC'));
$listLimit     = (int) ($this->state->get('list.limit'));
$filterApplied = $this->escape($this->state->get('filter.applied'));
$filterBranch  = $this->escape($this->state->get('filter.branch'));
$filterLabel   = $this->state->get('filter.label');
$filterRtc     = $this->escape($this->state->get('filter.rtc'));
$filterNpm     = $this->escape($this->state->get('filter.npm'));
$visible       = '';

if ($filterApplied || $filterBranch || $filterLabel || $filterRtc || $filterNpm)
{
	$visible = 'js-stools-container-filters-visible';
}
?>
<form action="<?php echo Route::_('index.php?option=com_patchtester&view=pulls'); ?>" method="post" name="adminForm" id="adminForm">
	<div class="row">
		<div class="col-md-12">
			<div id="j-main-container" class="j-main-container">
				<div class="js-stools" role="search">
					<div class="js-stools-container-bar">
						<div class="btn-toolbar">
							<div class="btn-group mr-2">
								<div class="input-group">
									<label for="filter_search" class="sr-only">
										<?php echo Text::_('COM_PATCHTESTER_FILTER_SEARCH_DESCRIPTION'); ?>
									</label>
									<input type="text" name="filter_search" id="filter_search" value="<?php echo $this->escape($this->state->get('filter.search')); ?>" class="form-control" placeholder="<?php echo Text::_('JSEARCH_FILTER'); ?>">
									<div role="tooltip" id="filter_search-desc">
										<?php echo $this->escape(Text::_('COM_PATCHTESTER_FILTER_SEARCH_DESCRIPTION')); ?>
									</div>
									<span class="input-group-append">
										<button type="submit" class="btn btn-primary" aria-label="<?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?>">
											<span class="fa fa-search" aria-hidden="true"></span>
										</button>
									</span>
								</div>
							</div>
							<div class="btn-group">
								<button type="button" class="btn btn-primary hasTooltip js-stools-btn-filter">
									<?php echo Text::_('JFILTER_OPTIONS'); ?>
									<span class="fa fa-angle-down" aria-hidden="true"></span>
								</button>
								<button type="button" class="btn btn-primary js-stools-btn-clear mr-2">
									<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>
								</button>
							</div>
							<div class="ordering-select">
								<div class="js-stools-field-list">
									<select name="list_fullordering" id="list_fullordering" class="custom-select" onchange="this.form.submit()">
										<option value=""><?php echo Text::_('JGLOBAL_SORT_BY'); ?></option>
										<?php echo HTMLHelper::_('select.options', $this->getSortFields(), 'value', 'text', $listOrder); ?>
									</select>
								</div>
								<div class="js-stools-field-list">
									<span class="sr-only">
										<label id="list_limit-lbl" for="list_limit"><?php echo Text::_('JGLOBAL_LIST_LIMIT'); ?></label>
									</span>
									<select name="list_limit" id="list_limit" class="custom-select" onchange="this.form.submit()">
										<?php echo HTMLHelper::_('select.options', $this->getLimitOptions(), 'value', 'text', $listLimit); ?>
									</select>
								</div>
							</div>
						</div>
					</div>
					<!-- Filters div -->
					<div class="js-stools-container-filters clearfix <?php echo $visible; ?>">
						<div class="js-stools-field-filter">
							<select name="filter_applied" class="custom-select" onchange="this.form.submit();">
								<option value=""><?php echo Text::_('COM_PATCHTESTER_FILTER_APPLIED_PATCHES'); ?></option>
								<option value="yes"<?php echo $filterApplied === 'yes' ? ' selected="selected"' : ''; ?>><?php echo Text::_('COM_PATCHTESTER_APPLIED'); ?></option>
								<option value="no"<?php echo $filterApplied === 'no' ? ' selected="selected"' : ''; ?>><?php echo Text::_('COM_PATCHTESTER_NOT_APPLIED'); ?></option>
							</select>
						</div>
						<div class="js-stools-field-filter">
							<select name="filter_rtc" class="custom-select" onchange="this.form.submit();">
								<option value=""><?php echo Text::_('COM_PATCHTESTER_FILTER_RTC_PATCHES'); ?></option>
								<option value="yes"<?php echo $filterRtc === 'yes' ? ' selected="selected"' : ''; ?>><?php echo Text::_('COM_PATCHTESTER_RTC'); ?></option>
								<option value="no"<?php echo $filterRtc === 'no' ? ' selected="selected"' : ''; ?>><?php echo Text::_('COM_PATCHTESTER_NOT_RTC'); ?></option>
							</select>
						</div>
                        <div class="js-stools-field-filter">
                            <select name="filter_npm" class="custom-select" onchange="this.form.submit();">
                                <option value=""><?php echo Text::_('COM_PATCHTESTER_FILTER_NPM_PATCHES'); ?></option>
                                <option value="yes"<?php echo $filterNpm === 'yes' ? ' selected="selected"' : ''; ?>><?php echo Text::_('COM_PATCHTESTER_NPM'); ?></option>
                                <option value="no"<?php echo $filterNpm === 'no' ? ' selected="selected"' : ''; ?>><?php echo Text::_('COM_PATCHTESTER_NOT_NPM'); ?></option>
                            </select>
                        </div>
						<div class="js-stools-field-filter">
							<select name="filter_label[]" class="custom-select" multiple="" onchange="this.form.submit();" size="5">
								<option value=""><?php echo Text::_('COM_PATCHTESTER_FILTER_LABEL'); ?></option>
								<?php echo HTMLHelper::_('select.options', $this->labels, 'text', 'text', $filterLabel, false); ?>
							</select>
						</div>
						<div class="js-stools-field-filter">
							<select name="filter_branch" class="custom-select" onchange="this.form.submit();">
								<option value=""><?php echo Text::_('COM_PATCHTESTER_FILTER_BRANCH'); ?></option>
								<?php echo HTMLHelper::_('select.options', $this->branches, 'text', 'text', $filterBranch, false); ?>
							</select>
						</div>
					</div>
				</div>
				<div id="j-main-container" class="j-main-container">
					<?php if (empty($this->items)) : ?>
						<div class="alert alert-info">
							<span class="fa fa-info-circle" aria-hidden="true"></span><span class="sr-only"><?php echo Text::_('INFO'); ?></span>
							<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
						</div>
					<?php else : ?>
						<table class="table">
							<caption id="captionTable" class="sr-only">
								<?php echo Text::_('COM_PATCHTESTER_PULLS_TABLE_CAPTION'); ?>, <?php echo Text::_('JGLOBAL_SORTED_BY'); ?>
							</caption>
							<thead>
								<tr>
									<th scope="col" style="width:5%" class="text-center">
										<?php echo Text::_('COM_PATCHTESTER_PULL_ID'); ?>
									</th>
									<th scope="col" style="min-width:100px">
										<?php echo Text::_('JGLOBAL_TITLE'); ?>
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
					<?php endif; ?>

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
