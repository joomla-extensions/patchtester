<?php
/**
 * Patch testing component for the Joomla! CMS
 *
 * @copyright  Copyright (C) 2011 - 2012 Ian MacLennan, Copyright (C) 2013 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

use Joomla\CMS\Language\Text;
use PatchTester\View\Pulls\PullsHtmlView;

/** @var PullsHtmlView $this */

foreach ($this->items as $i => $item) :
	$status = '';

	if ($item->applied) :
		$status = ' class="table-active"';
	endif;
?>
	<tr<?php echo $status; ?>>
		<th scope="row" class="text-center">
			<?php echo $item->pull_id; ?>
		</th>
		<td>
			<span><?php echo $this->escape($item->title); ?></span>
			<div role="tooltip" id="tip<?php echo $i; ?>">
				<?php echo $this->escape($item->description); ?>
			</div>
			<div class="row">
				<div class="col-md-auto">
					<a class="badge bg-info" href="<?php echo $item->pull_url; ?>" target="_blank">
						<?php echo Text::_('COM_PATCHTESTER_VIEW_ON_GITHUB'); ?>
					</a>
				</div>
				<?php if ($this->trackerAlias) : ?>
				<div class="col-md-auto">
					<a class="badge bg-info"
					   href="https://issues.joomla.org/tracker/<?php echo $this->trackerAlias; ?>/<?php echo $item->pull_id; ?>"
					   target="_blank">
						<?php echo Text::_('COM_PATCHTESTER_VIEW_ON_JOOMLA_ISSUE_TRACKER'); ?>
					</a>
				</div>
				<?php endif; ?>
				<?php if ($item->applied) : ?>
					<div class="col-md-auto">
						<span class="badge bg-info"><?php echo Text::sprintf('COM_PATCHTESTER_APPLIED_COMMIT_SHA', substr($item->sha, 0, 10)); ?></span>
					</div>
				<?php endif; ?>
			</div>
            <?php if (count($item->labels) > 0) : ?>
            <div class="row">
                <div class="col-md-auto">
                <?php foreach ($item->labels as $label): ?>
                    <?php
                        switch (strtolower($label->name))
                        {
                            case 'a11y':
                            case 'conflicting files':
                            case 'documentation required':
                            case 'information required':
                            case 'j3 issue':
	                        case 'language change':
	                        case 'mysql 5.7':
	                        case 'needs new owner':
	                        case 'no code attached yet':
	                        case 'pbf':
                            case 'pr-3.9-dev':
                            case 'pr-3.10-dev':
                            case 'pr-4.1-dev':
                            case 'pr-i10n_4.0-dev':
	                        case 'pr-staging':
                            case 'release blocker':
                            case 'rfc':
                            case 'test instructions missing':
                            case 'updates requested':
	                            $textColor = '000000';
                                break;
                            default:
                                $textColor = 'FFFFFF';
                                break;
                        }
                    ?>
                    <span class="badge" style="color: #<?php echo $textColor; ?>; background-color: #<?php echo $label->color; ?>"><?php echo $label->name; ?></span>
                <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
		</td>
		<td class="d-none d-md-table-cell text-center">
			<?php echo $this->escape($item->branch); ?>
		</td>
		<td class="d-none d-md-table-cell text-center">
			<?php if ($item->is_rtc) : ?>
				<span class="badge bg-success"><?php echo Text::_('JYES'); ?></span>
			<?php else : ?>
				<span class="badge bg-secondary"><?php echo Text::_('JNO'); ?></span>
			<?php endif; ?>
		</td>
		<td class="text-center">
			<?php if ($item->applied) : ?>
				<span class="badge bg-success"><?php echo Text::_('COM_PATCHTESTER_APPLIED'); ?></span>
			<?php else : ?>
				<span class="badge bg-secondary"><?php echo Text::_('COM_PATCHTESTER_NOT_APPLIED'); ?></span>
			<?php endif; ?>
		</td>
		<td class="text-center">
			<?php if ($item->applied) : ?>
				<button type="button" class="btn btn-sm btn-success submitPatch"
						data-task="revert" data-id="<?php echo (int) $item->applied; ?>"><?php echo Text::_('COM_PATCHTESTER_REVERT_PATCH'); ?></button>
			<?php else : ?>
				<button type="button" class="btn btn-sm btn-primary submitPatch"
						data-task="apply" data-id="<?php echo (int) $item->pull_id; ?>"><?php echo Text::_('COM_PATCHTESTER_APPLY_PATCH'); ?></button>
			<?php endif; ?>
		</td>
	</tr>
<?php endforeach;
