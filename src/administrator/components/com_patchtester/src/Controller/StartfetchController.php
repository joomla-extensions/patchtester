<?php

/**
 * Patch testing component for the Joomla! CMS
 *
 * @copyright  Copyright (C) 2011 - 2012 Ian MacLennan, Copyright (C) 2013 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

namespace Joomla\Component\Patchtester\Administrator\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;
use Joomla\Component\Patchtester\Administrator\Helper\Helper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Controller class to start fetching remote data
 *
 * @since  2.0
 */
class StartfetchController extends BaseController
{
    /**
     * Execute the controller.
     *
     * @return  void  Redirects the application
     *
     * @since   2.0
     */
    public function execute($task): void
    {
        $this->app->setHeader('Expires', 'Mon, 1 Jan 2001 00:00:00 GMT', true);
        $this->app->setHeader('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT', true);
        $this->app->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0', false);
        $this->app->setHeader('Pragma', 'no-cache');
        $this->app->setHeader('Content-Type', $this->app->mimeType . '; charset=' . $this->app->charSet);

        if (!Session::checkToken('request')) {
            $response = new JsonResponse(new \Exception(Text::_('JINVALID_TOKEN'), 403));
            $this->app->sendHeaders();
            echo json_encode($response);
            $this->app->close(1);
        }

        // Make sure we can fetch the data from GitHub - throw an error on < 10 available requests
        try {
            $rateResponse = Helper::initializeGithub()->getRateLimit();
            $rate         = json_decode($rateResponse->body);
        } catch (\Exception $e) {
            $response = new JsonResponse(new \Exception(Text::sprintf('COM_PATCHTESTER_COULD_NOT_CONNECT_TO_GITHUB', $e->getMessage()), $e->getCode(), $e));
            $this->app->sendHeaders();
            echo json_encode($response);
            $this->app->close(1);
        }

        // If over the API limit, we can't build this list
        if ($rate->resources->core->remaining < 10) {
            $response = new JsonResponse(new \Exception(Text::sprintf('COM_PATCHTESTER_API_LIMIT_LIST', Factory::getDate($rate->resources->core->reset)), 429));
            $this->app->sendHeaders();
            echo json_encode($response);
            $this->app->close(1);
        }

        $testsModel = Factory::getApplication()->bootComponent('com_patchtester')->getMVCFactory()->createModel('Tests', 'Administrator', ['ignore_request' => true]);
        try {
            // Sanity check, ensure there aren't any applied patches
            if (\count($testsModel->getAppliedPatches()) >= 1) {
                $response = new JsonResponse(new \Exception(Text::_('COM_PATCHTESTER_ERROR_APPLIED_PATCHES'), 500));
                $this->app->sendHeaders();
                echo json_encode($response);
                $this->app->close(1);
            }
        } catch (\Exception $e) {
            $response = new JsonResponse($e);
            $this->app->sendHeaders();
            echo json_encode($response);
            $this->app->close(1);
        }

        // We're able to successfully pull data, prepare our environment
        Factory::getApplication()->getSession()->set('com_patchtester_fetcher_page', 1);
        $response = new JsonResponse(
            [
                'complete' => false,
                'header'   => Text::_('COM_PATCHTESTER_FETCH_PROCESSING', true),
            ],
            Text::sprintf('COM_PATCHTESTER_FETCH_PAGE_NUMBER', 1),
            false,
            true
        );
        $this->app->sendHeaders();
        echo json_encode($response);
        $this->app->close();
    }
}
