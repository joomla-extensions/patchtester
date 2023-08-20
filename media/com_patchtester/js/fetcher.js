/**
 * Patch testing component for the Joomla! CMS
 *
 * @copyright  Copyright (C) 2011 - 2012 Ian MacLennan, Copyright (C) 2013 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

if (typeof jQuery === 'undefined') {
    throw new Error('PatchFetcher JavaScript requires jQuery')
}

if (typeof Joomla === 'undefined') {
    throw new Error('PatchFetcher JavaScript requires the Joomla core JavaScript API')
}

!function (jQuery, Joomla, window) {
    'use strict';

    /**
     * Initialize the PatchFetcher object
     *
     * @constructor
     */
    var PatchFetcher = function () {
        var offset = null,
            progress = null,
            path = 'index.php?option=com_patchtester&tmpl=component&format=json',
            lastPage = null,
            progressBar = jQuery('#progress-bar');

        var initialize = function () {
            offset = 0;
            progress = 0;
            path = path + '&' + jQuery('#patchtester-token').attr('name') + '=1';

            getRequest('startfetch');
        };

        var getRequest = function (task) {
            jQuery.ajax({
                type: 'GET',
                url: path,
                data: `task=${task}.${task}`,
                dataType: 'json',
                success: function (response, textStatus, xhr) {
                    try {
                        if (response === null) {
                            throw textStatus;
                        }

                        if (response.error) {
                            throw response;
                        }

                        if (response.success === false) {
                            throw response;
                        }

                        // Store the last page if it is part of this request and not a boolean false
                        if (typeof response.data.lastPage !== 'undefined' && response.data.lastPage !== false) {
                            lastPage = response.data.lastPage;
                        }

                        // Update the progress bar if we have the data to do so
                        if (typeof response.data.page !== 'undefined') {
                            progress = (response.data.page / lastPage) * 100;

                            if (progress < 100) {
                                progressBar.css('width', progress + '%').attr('aria-valuenow', progress);
                            } else {
                                // Both BS2 and BS4 classes are targeted to keep this script simple
                                progressBar
                                    .removeClass('bar-success bg-success')
                                    .addClass('bar-warning bg-warning')
                                    .css('width', progress + '%')
                                    .attr('aria-valuemin', 100)
                                    .attr('aria-valuemax', 200)
                                    .attr('aria-valuenow', progress);
                            }
                        }

                        jQuery('#patchtester-progress-message').html(response.message);

                        if (response.data.header) {
                            jQuery('#patchtester-progress-header').html(response.data.header);
                        }

                        if (!response.data.complete) {
                            // Send another request
                            getRequest('fetch');
                        } else {
                            jQuery('#progress').remove();
                            jQuery('#modal-sync  button.btn-close', window.parent.document).trigger('click');
                        }
                    } catch (error) {
                        try {
                            if (response.error || response.success === false) {
                                jQuery('#patchtester-progress-header').text(Joomla.JText._('COM_PATCHTESTER_FETCH_AN_ERROR_HAS_OCCURRED'));
                                jQuery('#patchtester-progress-message').html(response.message);
                            }
                        } catch (ignore) {
                            if (error === '') {
                                error = Joomla.JText._('COM_PATCHTESTER_NO_ERROR_RETURNED');
                            }

                            jQuery('#patchtester-progress-header').text(Joomla.JText._('COM_PATCHTESTER_FETCH_AN_ERROR_HAS_OCCURRED'));
                            jQuery('#patchtester-progress-message').html(error);
                            jQuery('#progress').remove();
                        }
                    }
                    return true;
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    var json = (typeof jqXHR === 'object' && jqXHR.responseText) ? jqXHR.responseText : null;
                    jQuery('#patchtester-progress-header').text(Joomla.JText._('COM_PATCHTESTER_FETCH_AN_ERROR_HAS_OCCURRED'));
                    jQuery('#patchtester-progress-message').html(json);
                    jQuery('#progress').remove();
                }
            });
        };

        initialize();
    };

    jQuery(function () {
        new PatchFetcher();

        if (typeof window.parent.SqueezeBox === 'object') {
            jQuery(window.parent.SqueezeBox).on('close', function () {
                window.parent.location.reload(true);
            });
        }
    });
}(jQuery, Joomla, window);
