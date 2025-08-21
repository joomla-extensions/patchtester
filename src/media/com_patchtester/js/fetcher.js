/**
 * Patch testing component for the Joomla! CMS
 *
 * @copyright  Copyright (C) 2023 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

if (typeof Joomla === 'undefined') {
    throw new Error('PatchFetcher JavaScript requires the Joomla core JavaScript API')
}

const defaultSettings = {
    progress: null,
    lastPage: null,
    baseURL: `${Joomla.getOptions('system.paths').baseFull}index.php?option=com_patchtester&tmpl=component&format=json`,
};

class PatchFetcher {
  constructor(settings = defaultSettings) {
    this.url = new URL(settings.baseURL);
    this.progress = settings.progress;
    this.lastPage = settings.lastPage;

    this.progressBar = document.getElementById('progress-bar');
    this.url.searchParams.append(document.getElementById('patchtester-token').getAttribute('name'), 1);

    this.request('startfetch');
  }

  request(task) {
    this.url.searchParams.append('task', `${task}.${task}`);

    Joomla.request({
      url: this.url.toString(),
      method: 'GET',
      headers: { 'Content-Type': 'application/json' },
      perform: true,
      onSuccess: (response) => {
        response = JSON.parse(response)
        try {
          if (response === null || response.error || response.success === false) {
            throw response;
          }
          // {"success":true,"message":"Processing page 1 of GitHub data","messages":null,"data":{"complete":false,"header":"Processing data from GitHub"}}
          // Store the last page if it is part of this request and not a boolean false
          if (typeof response.data.lastPage !== 'undefined' && response.data.lastPage !== false) {
            this.lastPage = response.data.lastPage;
          }

          // Update the progress bar if we have the data to do so
          if (typeof response.data.page !== 'undefined') {
            this.progress = (response.data.page / this.lastPage) * 100;

            if (this.progress < 100) {
              this.progressBar.style.width = `${this.progress}%`;
              this.progressBar.setAttribute('aria-valuenow', this.progress);
            } else {
              // Both BS2 and BS4 classes are targeted to keep this script simple
              this.progressBar.classList.remove(['bar-success', 'bg-success']);
              this.progressBar.classList.remove(['bar-warning', 'bg-warning']);
              this.progressBar.style.width = `${this.progress}%`;
              this.progressBar.setAttribute('aria-valuemin', 100);
              this.progressBar.setAttribute('aria-valuemax', 200);
              this.progressBar.setAttribute('aria-valuenow', this.progress);
            }
          }

          document.getElementById('patchtester-progress-message').innerHTML = Joomla.sanitizeHtml(response.message);

          if (response.data.header) {
            document.getElementById('patchtester-progress-header').innerHTML = Joomla.sanitizeHtml(response.data.header);
          }

          if (!response.data.complete) {
            this.url.searchParams.append(document.querySelector('#patchtester-token').getAttribute('name'), 1);
            this.url.searchParams.append('task', `${task}.${task}`);
            this.request('fetch');
          } else {
            document.getElementById('progress').remove();
            window.parent.document.querySelector('#modal-sync button.btn-close').click();
          }
        } catch (error) {
          try {
            if (response.error || response.success === false) {
              document.getElementById('patchtester-progress-header').innerText = Joomla.JText._('COM_PATCHTESTER_FETCH_AN_ERROR_HAS_OCCURRED');
              document.getElementById('patchtester-progress-message').innerHTML = Joomla.sanitizeHtml(response.message);
            }
          } catch (ignore) {
            if (error === '') {
              error = Joomla.JText._('COM_PATCHTESTER_NO_ERROR_RETURNED');
            }

            document.getElementById('patchtester-progress-header').innerText = Joomla.JText._('COM_PATCHTESTER_FETCH_AN_ERROR_HAS_OCCURRED');
            document.getElementById('patchtester-progress-message').innerHTML = Joomla.sanitizeHtml(error);
            document.getElementById('progress').remove();
          }
        }
        return true;
      },
      onError: (jqXHR) => {
        const json = (typeof jqXHR === 'object' && jqXHR.responseText) ? jqXHR.responseText : null;
        document.getElementById('patchtester-progress-header').innerText = Joomla.JText._('COM_PATCHTESTER_FETCH_AN_ERROR_HAS_OCCURRED');
        document.getElementById('patchtester-progress-message').innerHTML = Joomla.sanitizeHtml(json);
        document.getElementById('progress').remove();
      }
    });
  }
}

new PatchFetcher();
