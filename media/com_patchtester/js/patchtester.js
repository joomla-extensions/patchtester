/**
 * Patch testing component for the Joomla! CMS
 *
 * @copyright  Copyright (C) 2011 - 2012 Ian MacLennan, Copyright (C) 2013 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

if (typeof Joomla === 'undefined') {
    throw new Error('PatchTester JavaScript requires the Joomla core JavaScript API')
}

Joomla.submitbutton = (task) => {
  if (task !== 'reset' || confirm(Joomla.JText._('COM_PATCHTESTER_CONFIRM_RESET', 'Resetting will attempt to revert all applied patches and removes all backed up files. This may result in a corrupted environment. Are you sure you want to continue?'))) {
    Joomla.submitform(task);
  }
};

const PatchTester = {
  /**
   * Process the patch action
   *
   * @param {String} task The task to perform
   * @param {Number} id   The item ID
   */
  submitpatch: function (task, id) {
    var idField = document.getElementById('pull_id');
    idField.value = id;

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
function patchSubmit(event) {
  const currentTarget = event.currentTarget;
  const task = `${currentTarget.dataset.task}.${currentTarget.dataset.task}`
  const id = parseInt(currentTarget.dataset.id)
  
  PatchTester.submitpatch(task, id);
}


document.querySelectorAll(".submitPatch").forEach((element) => element.addEventListener("click", patchSubmit));
