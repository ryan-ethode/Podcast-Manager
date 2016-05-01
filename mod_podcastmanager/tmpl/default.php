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

defined('_JEXEC') or die;
?>

<div<?php if ($moduleclass_sfx) : echo ' class="' . $moduleclass_sfx . '"'; endif; ?>>
	<div>
		<?php echo $params->get('text'); ?>
	</div>
	<div>
		<a href="<?php echo $link; ?>">
			<?php echo JHtml::_('image', 'mod_podcastmanager/podcast-mini2.png', JText::_('MOD_PODCASTMANAGER_PODCASTFEED'), null, true);; ?>
		</a>
	</div>
	<?php if ($params->get('plainlink') == 1) : ?>
	<div>
		<a href="<?php echo $plainlink; ?>">
			<?php echo JText::_('MOD_PODCASTMANAGER_FULLFEED');?>
		</a>
	</div>
	<?php endif; ?>
</div>
