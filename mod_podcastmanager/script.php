<?php
/**
 * Podcast Manager for Joomla!
 *
 * @package     PodcastManager
 * @subpackage  mod_podcastmanager
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
 * @subpackage  mod_podcastmanager
 * @since       1.8
 */
class Mod_PodcastManagerInstallerScript
{
	/**
	 * Function to act prior to installation process begins
	 *
	 * @param   string                   $type    The action being performed
	 * @param   JInstallerAdapterModule  $parent  The function calling this method
	 *
	 * @return  mixed  Boolean false on failure, void otherwise
	 *
	 * @since   1.8
	 */
	public function preflight($type, $parent)
	{
		// Check if Podcast Manager is installed
		if (!is_dir(JPATH_BASE . '/components/com_podcastmanager'))
		{
			JError::raiseNotice(null, JText::_('MOD_PODCASTMANAGER_ERROR_COMPONENT'));

			return false;
		}

		return true;
	}

	/**
	 * Function to perform changes during update
	 *
	 * @param   JInstallerAdapterModule  $parent  The class calling this method
	 *
	 * @return  void
	 *
	 * @since   2.1
	 */
	public function update($parent)
	{
		// Get the pre-update version
		$version = $this->getVersion();

		// If in error, throw a message about the language files
		if ($version == 'Error')
		{
			JError::raiseNotice(null, JText::_('COM_PODCASTMANAGER_ERROR_INSTALL_UPDATE'));

			return;
		}
	}

	/**
	 * Function to get the currently installed version from the manifest cache
	 *
	 * @return  string  The version that is installed
	 *
	 * @since   2.1
	 */
	private function getVersion()
	{
		// Get the record from the database
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName('manifest_cache'))
			->from($db->quoteName('#__extensions'))
			->where($db->quoteName('element') . ' = ' . $db->quote('mod_podcastmanager'));

		try
		{
			$manifest = $db->setQuery($query)->loadObject();
		}
		catch (RuntimeException $e)
		{
			JError::raiseWarning(1, JText::sprintf('JLIB_INSTALLER_ERROR_SQL_ERROR', $e->getMessage()));

			return 'Error';
		}

		// Decode the JSON
		$record = json_decode($manifest->manifest_cache);

		// Get the version
		return $record->version;
	}
}
