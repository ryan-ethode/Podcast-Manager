<?php
/**
* Podcast Manager for Joomla!
*
* @copyright	Copyright (C) 2011 Michael Babker. All rights reserved.
* @license		GNU/GPL - http://www.gnu.org/copyleft/gpl.html
*
* Podcast Manager is based upon the ideas found in Podcast Suite created by Joe LeBlanc
* Original copyright (c) 2005 - 2008 Joseph L. LeBlanc and released under the GPLv2 license
*/

/**
 * Installation class to perform additional changes during install/uninstall/update
 *
 * @package		Podcast Manager
 * @subpackage	com_podcastmanager
 * @since		1.7
 */
class Com_PodcastManagerInstallerScript {

	/**
	 * Function to act prior to installation process begins
	 *
	 * @param	string	$type	The action being performed
	 * @param	string	$parent	The function calling this method
	 *
	 * @return	void
	 * @since	1.7
	 */
	function preflight($type, $parent) {
		// Requires Joomla! 1.7
		$jversion = new JVersion();
		if (version_compare($jversion->getShortVersion(), '1.7', 'lt')) {
			JError::raiseWarning(null, JText::_('COM_PODCASTMANAGER_ERROR_INSTALL_J17'));
			return false;
		}
	}

	/**
	 * Function to perform changes during uninstall
	 *
	 * @param	string	$parent	The function calling this method
	 *
	 * @return	void
	 * @since	1.8
	 */
	function uninstall($parent) {
		// Build a menu record for the media component to prevent the "cannot delete admin menu" error
		// Get the component's ID from the database
		$option	= 'com_podcastmedia';
		$db = JFactory::getDBO();
		$query	= $db->getQuery(true);
		$query->select('extension_id');
		$query->from('#__extensions');
		$query->where('element = '.$db->quote($option));
		$db->setQuery($query);
		$component_id = $db->loadResult();

		// Add the record
		$table	= JTable::getInstance('menu');

		$data = array();
		$data['menutype'] = 'main';
		$data['client_id'] = 1;
		$data['title'] = $option;
		$data['alias'] = $option;
		$data['link'] = 'index.php?option='.$option;
		$data['type'] = 'component';
		$data['published'] = 0;
		$data['parent_id'] = 1;
		$data['component_id'] = $component_id;
		$data['img'] = 'class:component';
		$data['home'] = 0;

		// All the table processing without error checks since we're hacking to prevent an error message
		if (!$table->setLocation(1, 'last-child') || !$table->bind($data) || !$table->check() || !$table->store()) {
			echo 'Just another error, keep going';
			continue;
		}
	}

	/**
	 * Function to perform updates when method=upgrade is used
	 *
	 * @param	string	$parent	The function calling this method
	 *
	 * @return	void
	 * @since	1.7
	 */
	function update($parent) {
		// Check the currently installed version
		$version	= $this->getVersion();

		// If upgrading from 1.6, run the 1.7 schema updates
		if (substr($version, 0, 3) == '1.6') {
			// Update the tables then create the new feed
			$this->db17Update();
			$this->createFeed();
		}

		// If upgrading from 1.7 Beta releases, update the description field
		if (strpos($version, '1.7 Beta') != false) {
			$db = JFactory::getDBO();
			$query	= 'ALTER TABLE `#__podcastmanager_feeds` CHANGE `description` `description` varchar(5120) NOT NULL default '.$db->quote('');
			$db->setQuery($query);
			if (!$db->query()) {
				JError::raiseWarning(1, JText::sprintf('JLIB_INSTALLER_ERROR_SQL_ERROR', $db->stderr(true)));
				return false;
			}
		}
	}

	/**
	 * Function to create a new feed based on the 1.6 parameters when upgrading to 1.7
	 *
	 * @return	void
	 * @since	1.7
	 */
	protected function createFeed() {
		// Get the record from the database
		$db = JFactory::getDBO();
		$query	= $db->getQuery(true);
		$query->select($db->quoteName('params'));
		$query->from($db->quoteName('#__extensions'));
		$query->where($db->quoteName('element').' = "com_podcastmanager"');
		$db->setQuery($query);
		$record = $db->loadObject();

		// Decode the JSON
		$params	= json_decode($record->params);

		// Query to create new feed record
		$addFeed	= 'INSERT INTO `#__podcastmanager_feeds` (`id`, `name`, `subtitle`, `description`, `copyright`,'.
				  ' `explicit`, `block`, `ownername`, `owneremail`, `keywords`, `author`, `image`, `category1`,'.
				  ' `category2`, `category3`, `published`) VALUES'.
				  ' (1, '.$db->quote($params->title).', '.$db->quote($params->itSubtitle).', '.$db->quote($params->description).','.
				  $db->quote($params->copyright).', '.$db->quote($params->itExplicit).', '.$db->quote($params->itBlock).','.
				  $db->quote($params->itOwnerName).', '.$db->quote($params->itOwnerEmail).', '.$db->quote($params->itKeywords).','.
				  $db->quote($params->itAuthor).', '.$db->quote($params->itImage).', '.$db->quote($params->itCategory1).','.
				  $db->quote($params->itCategory2).', '.$db->quote($params->itCategory3).', '.$db->quote('1').');';
		$db->setQuery($addFeed);
		if (!$db->query()) {
			JError::raiseWarning(1, JText::sprintf('JLIB_INSTALLER_ERROR_SQL_ERROR', $db->stderr(true)));
			return false;
		}

		// Set the feed on existing podcasts to this feed
		$feed	= $db->getQuery(true);
		$query->update($db->quoteName('#__podcastmanager'));
		$query->set($db->quoteName('feedname').' = '.$db->quote('1'));
		$db->setQuery($feed);
		if (!$db->query()) {
			JError::raiseWarning(1, JText::sprintf('JLIB_INSTALLER_ERROR_SQL_ERROR', $db->stderr(true)));
			return false;
		}
	}

	/**
	 * Function to update the Podcast Manager tables from the 1.6 to 1.7 schema
	 *
	 * @return	void
	 * @since	1.7
	 */
	protected function db17Update() {
		echo '<p>Podcast Manager 1.6 to 1.7 SQL changes</p>';
		$db = JFactory::getDBO();

		// Get the update file
		$SQLupdate	= file_get_contents(dirname(__FILE__).'/admin/sql/updates/mysql/1.7.0.sql');
		$SQLupdate	.= file_get_contents(dirname(__FILE__).'/admin/sql/updates/mysql/1.7.1.sql');

		if ($SQLupdate === false) {
			return false;
		}

		// Create an array of queries from the sql file
		jimport('joomla.installer.helper');
		$queries = JInstallerHelper::splitSql($SQLupdate);

		if (count($queries) == 0) {
			continue;
		}

		// Process each query in the $queries array (split out of sql file).
		foreach ($queries as $query) {
			$query = trim($query);
			if ($query != '' && $query{0} != '#') {
				$db->setQuery($query);
				if (!$db->query()) {
					JError::raiseWarning(1, JText::sprintf('JLIB_INSTALLER_ERROR_SQL_ERROR', $db->stderr(true)));
					return false;
				}
			}
		}
	}

	/**
	 * Function to get the currently installed version from the manifest cache
	 *
	 * @return	string	$version	The base version that is installed
	 * @since	1.7
	 */
	protected function getVersion() {
		// Get the record from the database
		$db = JFactory::getDBO();
		$query	= $db->getQuery(true);
		$query->select($db->quoteName('manifest_cache'));
		$query->from($db->quoteName('#__extensions'));
		$query->where($db->quoteName('element').' = "com_podcastmanager"');
		$db->setQuery($query);
		$manifest = $db->loadObject();

		// Decode the JSON
		$record	= json_decode($manifest->manifest_cache);

		// Get the version
		$version	= $record->version;

		return $version;
	}
}
