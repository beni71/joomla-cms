<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_finder
 *
 * @copyright   (C) 2018 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Finder\Administrator\View\Searches;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Finder\Administrator\Model\SearchesModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * View class for a list of search terms.
 *
 * @since  4.0.0
 */
class HtmlView extends BaseHtmlView
{
    /**
     * True if gathering search statistics is enabled
     *
     * @var  boolean
     */
    protected $enabled;

    /**
     * An array of items
     *
     * @var  array
     */
    protected $items;

    /**
     * The pagination object
     *
     * @var  \Joomla\CMS\Pagination\Pagination
     */
    protected $pagination;

    /**
     * The model state
     *
     * @var  \Joomla\Registry\Registry
     */
    protected $state;

    /**
     * Form object for search filters
     *
     * @var    \Joomla\CMS\Form\Form
     *
     * @since  4.0.0
     */
    public $filterForm;

    /**
     * The active search filters
     *
     * @var    array
     *
     * @since  4.0.0
     */
    public $activeFilters;

    /**
     * The actions the user is authorised to perform
     *
     * @var    \Joomla\Registry\Registry
     *
     * @since  4.0.0
     */
    protected $canDo;

    /**
     * @var boolean
     *
     * @since  4.0.0
     */
    private $isEmptyState = false;

    /**
     * Display the view.
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     *
     * @return  void
     */
    public function display($tpl = null)
    {
        /** @var SearchesModel $model */
        $model = $this->getModel();

        $app                 = Factory::getApplication();
        $this->items         = $model->getItems();
        $this->pagination    = $model->getPagination();
        $this->state         = $model->getState();
        $this->filterForm    = $model->getFilterForm();
        $this->activeFilters = $model->getActiveFilters();
        $this->enabled       = $this->state->params->get('gather_search_statistics', 0);
        $this->canDo         = ContentHelper::getActions('com_finder');
        $uri                 = Uri::getInstance();
        $link                = 'index.php?option=com_config&view=component&component=com_finder&return=' . base64_encode($uri);
        $output              = HTMLHelper::_('link', Route::_($link), Text::_('JOPTIONS'));

        if (!\count($this->items) && $this->isEmptyState = $model->getIsEmptyState()) {
            $this->setLayout('emptystate');
        }

        // Check for errors.
        if (\count($errors = $model->getErrors())) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        // Check if component is enabled
        if (!$this->enabled) {
            // Check if the user has access to the component options
            if ($this->canDo->get('core.admin') || $this->canDo->get('core.options')) {
                $app->enqueueMessage(Text::sprintf('COM_FINDER_LOGGING_DISABLED', $output), 'warning');
            } else {
                $app->enqueueMessage(Text::_('COM_FINDER_LOGGING_DISABLED_NO_AUTH'), 'warning');
            }
        }

        // Prepare the view.
        $this->addToolbar();

        parent::display($tpl);
    }

    /**
     * Add the page title and toolbar.
     *
     * @return  void
     *
     * @since   1.6
     */
    protected function addToolbar()
    {
        $canDo   = $this->canDo;
        $toolbar = $this->getDocument()->getToolbar();

        ToolbarHelper::title(Text::_('COM_FINDER_MANAGER_SEARCHES'), 'search');

        if (!$this->isEmptyState) {
            if ($canDo->get('core.edit.state')) {
                $toolbar->standardButton('reset', 'JSEARCH_RESET', 'searches.reset')
                    ->icon('icon-refresh')
                    ->listCheck(false);
            }

            $toolbar->divider();
        }

        if ($canDo->get('core.admin') || $canDo->get('core.options')) {
            $toolbar->preferences('com_finder');
        }

        $toolbar->help('Smart_Search:_Search_Term_Analysis');
    }
}
