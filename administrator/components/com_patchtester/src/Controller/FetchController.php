<?php

/**
 * Patch testing component for the Joomla! CMS
 *
 * @copyright  Copyright (C) 2011 - 2012 Ian MacLennan, Copyright (C) 2013 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

namespace Joomla\Component\Patchtester\Administrator\Controller;

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\Component\Patchtester\Administrator\Model\PullsModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Controller class to fetch remote data
 *
 * @since  2.0
 */
class FetchController extends BaseController
{
    /**
     * Execute the controller.
     *
     * @return  void  Redirects the application
     *
     * @throws  Exception
     * @since   2.0
     */
    public function execute($task)
    {
        $this->app->setHeader('Expires', 'Mon, 1 Jan 2001 00:00:00 GMT', true);
        $this->app->setHeader('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT', true);
        $this->app->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0', false);
        $this->app->setHeader('Pragma', 'no-cache');
        $this->app->setHeader('Content-Type', $this->app->mimeType . '; charset=' . $this->app->charSet);
        $session = Factory::getApplication()->getSession();

        try {
            $page = $session->get('com_patchtester_fetcher_page', 1);
            /** @var PullsModel $model */
            $model = $this->app->bootComponent('com_patchtester')->getMVCFactory()->createModel('Pulls', 'Administrator', ['ignore_request' => true]);

            $status = $model->requestFromGithub($page);
        } catch (Exception $e) {
            $response = new JsonResponse($e);
            $this->app->sendHeaders();
            echo json_encode($response);
            $this->app->close(1);
        }

        if (isset($status['lastPage']) && $status['lastPage'] !== false) {
            $session->set('com_patchtester_fetcher_last_page', $status['lastPage']);
        }

        if ($status['complete'] || $page === $session->get('com_patchtester_fetcher_last_page', false)) {
            $status['complete'] = true;
            $status['header']   = Text::_('COM_PATCHTESTER_FETCH_SUCCESSFUL', true);
            $message = Text::_('COM_PATCHTESTER_FETCH_COMPLETE_CLOSE_WINDOW', true);
        } elseif (isset($status['page'])) {
            $session->set('com_patchtester_fetcher_page', $status['page']);
            $message = Text::sprintf('COM_PATCHTESTER_FETCH_PAGE_NUMBER', $status['page']);

            if ($session->has('com_patchtester_fetcher_last_page')) {
                $message = Text::sprintf(
                    'COM_PATCHTESTER_FETCH_PAGE_NUMBER_OF_TOTAL',
                    $status['page'],
                    $session->get('com_patchtester_fetcher_last_page')
                );
            }
        }

        $response = new JsonResponse($status, $message, false, true);
        $this->app->sendHeaders();
        echo json_encode($response);
        $this->app->close();
    }
}
