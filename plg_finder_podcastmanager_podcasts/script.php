<?php
/**
 * Podcast Manager for Joomla!
 *
 * @package     PodcastManager
 * @subpackage  plg_finder_podcastmanager_podcasts
 *
 * @copyright   Copyright (C) 2011-2015 Michael Babker. All rights reserved.
 * @license     GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 *
 * Podcast Manager is based upon the ideas found in Podcast Suite created by Joe LeBlanc
 * Original copyright (c) 2005 - 2008 Joseph L. LeBlanc and released under the GPLv2 license
 */

/**
 * Installation class to perform additional changes during install/uninstall/update
 *
 * @package     PodcastManager
 * @subpackage  plg_finder_podcastmanager_podcasts
 * @since       2.0
 */
class PlgFinderPodcastManager_PodcastsInstallerScript extends JInstallerScript
{
	/**
	 * Extension script constructor.
	 *
	 * @since   3.0
	 */
	public function __construct()
	{
		$this->extension     = 'podcastmanager_podcasts';
		$this->minimumJoomla = '3.6';
		$this->minimumPhp    = '5.4';
	}

	/**
	 * Function to act prior to installation process begins
	 *
	 * @param   string                   $type    The action being performed
	 * @param   JInstallerAdapterPlugin  $parent  The function calling this method
	 *
	 * @return  boolean
	 *
	 * @since   2.0
	 * @throws  RuntimeException
	 */
	public function preflight($type, $parent)
	{
		// Make sure we aren't uninstalling first
		if ($type != 'uninstall')
		{
			// Check if Podcast Manager is installed
			if (!is_dir(JPATH_BASE . '/components/com_podcastmanager'))
			{
				throw new RuntimeException(JText::_('PLG_FINDER_PODCASTMANAGER_PODCASTS_ERROR_COMPONENT'));
			}
		}

		return parent::preflight($type, $parent);
	}
}
