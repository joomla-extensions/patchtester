<?php

/**
 * Patch testing component for the Joomla! CMS
 *
 * @copyright  Copyright (C) 2011 - 2012 Ian MacLennan, Copyright (C) 2013 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later
 */

namespace PatchTester\View;

use Joomla\CMS\Filesystem\Path;
use Joomla\CMS\Language\Text;
use PatchTester\Model\AbstractModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Base HTML view for the patch testing component
 *
 * @since  4.0.0
 */
abstract class AbstractHtmlView extends AbstractView
{
    /**
     * The view layout.
     *
     * @var    string
     * @since  4.0.0
     */
    protected $layout = 'default';
/**
     * The paths queue.
     *
     * @var    SplPriorityQueue
     * @since  4.0.0
     */
    protected $paths;
/**
     * Method to instantiate the view.
     *
     * @param   AbstractModel     $model  The model object.
     * @param   SplPriorityQueue  $paths  The paths queue.
     *
     * @since   4.0.0
     */
    public function __construct($model, \SplPriorityQueue $paths = null)
    {
        parent::__construct($model);
// Setup dependencies.
        $this->paths = $paths ?: new \SplPriorityQueue();
    }

    /**
     * Method to escape output.
     *
     * @param   string  $output  The output to escape.
     *
     * @return  string  The escaped output.
     *
     * @since   4.0.0
     */
    public function escape($output)
    {
        // Escape the output.
        return htmlspecialchars($output, ENT_COMPAT, 'UTF-8');
    }

    /**
     * Method to get the view layout.
     *
     * @return  string  The layout name.
     *
     * @since   4.0.0
     */
    public function getLayout()
    {
        return $this->layout;
    }

    /**
     * Method to get the layout path.
     *
     * @param   string  $layout  The layout name.
     *
     * @return  mixed  The layout file name if found, false otherwise.
     *
     * @since   4.0.0
     */
    public function getPath($layout)
    {
        // Get the layout file name.
        $file = Path::clean($layout . '.php');
// Find the layout file path.
        $path = Path::find(clone $this->paths, $file);
        return $path;
    }

    /**
     * Method to get the view paths.
     *
     * @return  SplPriorityQueue  The paths queue.
     *
     * @since   4.0.0
     */
    public function getPaths()
    {
        return $this->paths;
    }

    /**
     * Load a template file -- first look in the templates folder for an override
     *
     * @param   string  $tpl  The name of the template source file; automatically searches the template paths and compiles as needed.
     *
     * @return  string  The output of the the template script.
     *
     * @since   4.0.0
     * @throws  \RuntimeException
     */
    public function loadTemplate($tpl = null)
    {
        // Get the path to the file
        $file = $this->getLayout();
        if (isset($tpl)) {
            $file .= '_' . $tpl;
        }

        $path = $this->getPath($file);
        if (!$path) {
            throw new \RuntimeException(Text::sprintf('JLIB_APPLICATION_ERROR_LAYOUTFILE_NOT_FOUND', $file), 500);
        }

        // Unset so as not to introduce into template scope
        unset($tpl);
        unset($file);
// Never allow a 'this' property
        if (isset($this->this)) {
            unset($this->this);
        }

        // Start an output buffer.
        ob_start();
// Load the template.
        include $path;
// Get the layout contents.
        return ob_get_clean();
    }

    /**
     * Method to render the view.
     *
     * @return  string  The rendered view.
     *
     * @since   4.0.0
     * @throws  RuntimeException
     */
    public function render()
    {
        // Get the layout path.
        $path = $this->getPath($this->getLayout());
// Check if the layout path was found.
        if (!$path) {
            throw new \RuntimeException('Layout Path Not Found');
        }

        // Start an output buffer.
        ob_start();
// Load the layout.
        include $path;
// Get the layout contents.
        $output = ob_get_clean();
        return $output;
    }

    /**
     * Method to set the view layout.
     *
     * @param   string  $layout  The layout name.
     *
     * @return  $this
     *
     * @since   4.0.0
     */
    public function setLayout($layout)
    {
        $this->layout = $layout;
        return $this;
    }

    /**
     * Method to set the view paths.
     *
     * @param   \SplPriorityQueue  $paths  The paths queue.
     *
     * @return  $this
     *
     * @since   4.0.0
     */
    public function setPaths(\SplPriorityQueue $paths)
    {
        $this->paths = $paths;
        return $this;
    }
}
