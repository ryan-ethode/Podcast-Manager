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
 * Podcast Manager base controller
 *
 * @package     PodcastManager
 * @subpackage  com_podcastmanager
 * @since       1.6
 */
class PodcastManagerController extends JControllerLegacy
{
	/**
	 * The default view.
	 *
	 * @var    string
	 * @since  1.6
	 */
	protected $default_view = 'cpanel';

	/**
	 * Method to display a view.
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached
	 * @param   array    $urlparams  An array of safe url parameters and their variable types,
	 *                               for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return  PodcastManagerController  Instance of $this to support chaining.
	 *
	 * @since   1.6
	 */
	public function display($cachable = false, $urlparams = array())
	{
		$input = JFactory::getApplication('administrator')->input;
		include_once JPATH_COMPONENT . '/helpers/podcastmanager.php';

		$view = $input->get('view', $this->default_view, 'word');
		$layout = $input->get('layout', $this->default_view, 'word');
		$id = $input->get('id', null, 'int');

		// Check for edit form.
		if ($view == 'feed' && $layout == 'edit' && !$this->checkEditId('com_podcastmanager.edit.feed', $id))
		{
			// Somehow the person just went to the form - we don't allow that.
			$this->setError(JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id));
			$this->setMessage($this->getError(), 'error');
			$this->setRedirect(JRoute::_('index.php?option=com_podcastmanager&view=feeds', false));

			return false;
		}
		elseif ($view == 'podcast' && $layout == 'edit' && !$this->checkEditId('com_podcastmanager.edit.podcast', $id))
		{
			// Somehow the person just went to the form - we don't allow that.
			$this->setError(JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id));
			$this->setMessage($this->getError(), 'error');
			$this->setRedirect(JRoute::_('index.php?option=com_podcastmanager&view=podcasts', false));

			return false;
		}

		return parent::display();
	}

	/**
	 * Executes the extension's migration steps
	 *
	 * @return  void
	 *
	 * @since   2.2
	 */
	public function migrate()
	{
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		$errors         = array();
		$input          = JFactory::getApplication('administrator')->input;
		$migrationTasks = json_decode(base64_decode($input->getBase64('migrationTasks')), true);

		if (count($migrationTasks))
		{
			$tasks = array_keys($migrationTasks);

			foreach ($tasks as $task)
			{
				switch ($task)
				{
					case 'noFeedType' :
					case 'noPodcastType' :
						include_once JPATH_COMPONENT . '/helpers/podcastmanager.php';

						try
						{
							PodcastManagerHelper::insertUcmRecords();
						}
						catch (RuntimeException $e)
						{
							$errors[] = JText::sprintf(
								'COM_PODCASTMANAGER_MIGRATION_ERROR_INSERTING_UCM_RECORDS',
								strtolower(str_replace(array('no', 'Type'), '', $task)),
								$e->getMessage()
							);
						}

						break;

					case 'noLayouts' :
						// To build the package URL, we'll need to get the extension's version
						$manifest = JApplicationHelper::parseXMLInstallFile(JPATH_ADMINISTRATOR . '/components/com_podcastmanager/podcastmanager.xml');
						$version  = $manifest['version'];
						$url      = 'https://github.com/BabDev/Podcast-Manager/releases/download/' . $version . '/files_podcastmanager_strapped_' . $version . '.zip';

						// Download the package at the URL given.
						$file = JInstallerHelper::downloadPackage($url);

						// Was the package downloaded?
						if (!$file)
						{
							$errors[] = JText::_('COM_PODCASTMANAGER_MIGRATION_ERROR_DOWNLOADING_PACKAGE');

							continue;
						}

						$tmpPath = JFactory::getConfig()->get('tmp_path');

						// Unpack the downloaded package file.
						$package = JInstallerHelper::unpack($tmpPath . '/' . $file, true);

						// Was the package unpacked?
						if (!$package || !$package['type'])
						{
							JInstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);
							$errors[] = JText::_('COM_PODCASTMANAGER_MIGRATION_ERROR_EXTRACTING_PACKAGE');

							continue;
						}

						// Get an installer instance.
						$installer = new JInstaller;

						// Install the package.
						if (!$installer->install($package['dir']))
						{
							// There was an error installing the package.
							$errors[] = JText::_('COM_PODCASTMANAGER_MIGRATION_ERROR_INSTALLING_PACKAGE');
						}

						// Cleanup the install files.
						if (!is_file($package['packagefile']))
						{
							$package['packagefile'] = JFactory::getConfig()->get('tmp_path') . '/' . $package['packagefile'];
						}

						JInstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);

						break;

					default:
						$errors[] = JText::sprintf('COM_PODCASTMANAGER_MIGRATION_ERROR_BAD_TASK', $task);

						break;
				}
			}
		}

		if (count($errors))
		{
			$message = JText::sprintf('COM_PODCASTMANAGER_MIGRATION_ERRORS', implode("\n", $errors));
			$msgType = 'error';
		}
		else
		{
			$message = JText::_('COM_PODCASTMANAGER_MIGRATION_SUCCESSFUL');
			$msgType = 'success';
		}

		$this->setRedirect(JRoute::_('index.php?option=com_podcastmanager'), $message, $msgType);
	}
}
