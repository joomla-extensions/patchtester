/**
 * Patch testing component for the Joomla! CMS
 *
 * @copyright   (C) 2023 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

if (typeof Joomla === 'undefined') {
    throw new Error('PatchTester JavaScript requires the Joomla core JavaScript API')
}

Joomla.submitbutton = (task) => {
  if (task !== 'reset' || confirm(Joomla.JText._('COM_PATCHTESTER_CONFIRM_RESET', 'Resetting will attempt to revert all applied patches and removes all backed up files. This may result in a corrupted environment. Are you sure you want to continue?'))) {
    Joomla.submitform(task);
  }
};

/**
 * EventListener which listens on submitPatch Button,
 * checks if it is an apply or revert method and
 * processes the patch action
 *
 * @param {Event} event
 */
document.querySelectorAll(".submitPatch").forEach((element) => element.addEventListener("click", (event) => {
  const value = document.getElementById('pull_id')?.value;
  
  if (value) {
    value = parseInt(event.currentTarget.dataset.id);
  }

  Joomla.submitform(`${event.currentTarget.dataset.task}.${currentTarget.dataset.task}`);
}));
