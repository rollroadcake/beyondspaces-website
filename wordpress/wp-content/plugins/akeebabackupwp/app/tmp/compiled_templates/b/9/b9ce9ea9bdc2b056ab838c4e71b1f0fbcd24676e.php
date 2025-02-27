<?php /* /var/www/html/wp-content/plugins/akeebabackupwp/app/Solo/ViewTemplates/CommonTemplates/FTPBrowser.blade.php */ ?>
<?php
/**
 * @package   solo
 * @copyright Copyright (c)2014-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Awf\Text\Text;

defined('_AKEEBA') or die();

?>
<?php /* FTP Browser */ ?>
<div class="modal fade" id="ftpdialog" tabindex="-1" role="dialog" aria-labelledby="ftpdialogLabel" aria-hidden="true"
     style="display: none;">
    <div class="akeeba-renderer-fef <?php echo ($this->getContainer()->appConfig->get('darkmode', -1) == 1) ? 'akeeba-renderer-fef--dark' : '' ?>">
        <h4 id="ftpdialogLabel">
			<?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_UI_FTPBROWSER_TITLE'); ?>
        </h4>

        <p class="instructions akeeba-block--info">
			<?php echo $this->getLanguage()->text('COM_AKEEBA_FTPBROWSER_LBL_INSTRUCTIONS'); ?>
        </p>
        <div class="error akeeba-block--failure" id="ftpBrowserErrorContainer">
            <h3><?php echo $this->getLanguage()->text('COM_AKEEBA_FTPBROWSER_LBL_ERROR'); ?></h3>
            <p id="ftpBrowserError"></p>
        </div>

        <ul id="ak_crumbs2" class="breadcrumb"></ul>

        <div class="folderBrowserWrapper" id="ftpBrowserWrapper">
            <table id="ftpBrowserFolderList" class="akeeba-table akeeba-table--striped">
            </table>
        </div>

        <div>
            <button type="button" id="ftpdialogOkButton" class="akeeba-btn--primary">
                <span class="akion-checkmark"></span>
				<?php echo $this->getLanguage()->text('COM_AKEEBA_BROWSER_LBL_USE'); ?>
            </button>

            <button type="button" id="ftpdialogCancelButton" class="akeeba-btn--red">
                <span class="akion-ios-close"></span>
				<?php echo $this->getLanguage()->text('SOLO_BTN_CANCEL'); ?>
            </button>
        </div>
    </div>
</div>
