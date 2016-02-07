<?php
/**
 * Podcast Manager for Joomla!
 *
 * @package     PodcastManager
 * @subpackage  com_podcastmanager
 *
 * @copyright   Copyright (C) 2011-2015 Michael Babker. All rights reserved.
 * @license     GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 *
 * Podcast Manager is based upon the ideas found in Podcast Suite created by Joe LeBlanc
 * Original copyright (c) 2005 - 2008 Joseph L. LeBlanc and released under the GPLv2 license
 */

defined('_JEXEC') or die;

/**
 * Feed management view class.
 *
 * @package     PodcastManager
 * @subpackage  com_podcastmanager
 * @since       1.7
 */
class PodcastManagerViewFeeds extends JViewLegacy
{
	/**
	 * The items to display
	 *
	 * @var    array
	 * @since  1.7
	 */
	protected $items;

	/**
	 * The pagination object
	 *
	 * @var    JPagination
	 * @since  1.7
	 */
	protected $pagination;

	/**
	 * The HTML markup for the sidebar
	 *
	 * @var    string
	 * @since  2.1
	 */
	protected $sidebar;

	/**
	 * The state information
	 *
	 * @var    JObject
	 * @since  1.7
	 */
	protected $state;

	/**
	 * The allowed item states for list filtering
	 *
	 * @var    array
	 * @since  2.0
	 */
	protected $states = ['published' => true, 'unpublished' => true, 'archived' => false, 'trashed' => true, 'all' => true];

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  The name of the template file to parse
	 *
	 * @return  mixed  A string if successful, otherwise a JError object.
	 *
	 * @since   1.7
	 */
	public function display($tpl = null)
	{
		// Load the submenu.
		PodcastManagerHelper::addSubmenu('feeds');

		$this->items      = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->state      = $this->get('State');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			JError::raiseError(500, implode("\n", $errors));

			return false;
		}

		// Require the front end routing helper
		JLoader::register('PodcastManagerHelperRoute', JPATH_COMPONENT_SITE . '/helpers/route.php');

		// Add the component media
		JHtml::_('stylesheet', 'podcastmanager/template.css', false, true, false);

		// Make text JS available
		JText::script('COM_PODCASTMANAGER_CONFIRM_FEED_DELETE');

		$this->addToolbar();

		$this->sidebar = JHtmlSidebar::render();

		return parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 * @since   1.7
	 */
	protected function addToolbar()
	{
		$canDo = PodcastManagerHelper::getActions();

		JToolbarHelper::title(JText::_('COM_PODCASTMANAGER_VIEW_FEEDS_TITLE'), 'podcastmanager.png');

		if ($canDo->get('core.create') || (count(PodcastManagerHelper::getAuthorisedFeeds('core.create')) > 0))
		{
			JToolbarHelper::addNew('feed.add');
		}

		if ($canDo->get('core.edit') || (count(PodcastManagerHelper::getAuthorisedFeeds('core.edit')) > 0)
			|| $canDo->get('core.edit.own') || (count(PodcastManagerHelper::getAuthorisedFeeds('core.edit.own')) > 0))
		{
			JToolbarHelper::editList('feed.edit');
		}

		if ($canDo->get('core.edit.state') || (count(PodcastManagerHelper::getAuthorisedFeeds('core.edit.state')) > 0))
		{
			JToolbarHelper::divider();
			JToolbarHelper::publish('feeds.publish', 'JTOOLBAR_PUBLISH', true);
			JToolbarHelper::unpublish('feeds.unpublish', 'JTOOLBAR_UNPUBLISH', true);
			JToolbarHelper::divider();
			JToolbarHelper::checkin('feeds.checkin');
			JToolbarHelper::divider();
		}

		if ($this->state->get('filter.published') == -2 && ($canDo->get('core.delete')
			|| (count(PodcastManagerHelper::getAuthorisedFeeds('core.delete')) > 0)))
		{
			JToolbarHelper::deleteList('', 'feeds.delete', 'JTOOLBAR_EMPTY_TRASH');
			JToolbarHelper::divider();
		}
		elseif ($canDo->get('core.edit.state') || (count(PodcastManagerHelper::getAuthorisedFeeds('core.edit.state')) > 0))
		{
			JToolbarHelper::trash('feeds.trash');
			JToolbarHelper::divider();
		}

		if ($canDo->get('core.admin'))
		{
			JToolbarHelper::preferences('com_podcastmanager');
		}
	}
}
