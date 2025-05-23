<?php /* /var/www/html/wp-content/plugins/akeebabackupwp/app/Solo/ViewTemplates/Main/warning_adblock.blade.php */ ?>
<?php
/**
 * @package   solo
 * @copyright Copyright (c)2014-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_AKEEBA') or die();

// Used for type hinting
/** @var \Solo\View\Main\Html $this */

?>
<div id="adblock-warning" class="akeeba-block--info small" style="display: none;">
	<?php echo $this->getLanguage()->text('SOLO_SETUP_LBL_ADBLOCK_WARNING'); ?>
</div>
