<?php /* /var/www/html/wp-content/plugins/akeebabackupwp/app/Solo/ViewTemplates/Sysconfig/appsetup.blade.php */ ?>
<?php
/**
 * @package   solo
 * @copyright Copyright (c)2014-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Awf\Text\Text;

defined('_AKEEBA') or die();

/** @var \Solo\View\Sysconfig\Html $this */

$config = $this->container->appConfig;
$inCMS  = $this->container->segment->get('insideCMS', false);

$timezone = $config->get('timezone', 'GMT');
$timezone = ($timezone == 'UTC') ? 'GMT' : $timezone;

/**
 * Remember to update wpcli/Command/Sysconfig.php in the WordPress application whenever this file changes.
 */
?>
<div class="akeeba-form-group">
    <label for="darkmode">
        <?php echo $this->getLanguage()->text('SOLO_CONFIG_DISPLAY_DARKMODE_LABEL'); ?>
    </label>
    <div class="akeeba-toggle">
        <?php echo $this->getContainer()->html->get('fefselect.radiolist', [
            $this->getContainer()->html->fefselect->option( '0', Text::_('AWF_NO'), ['attr' => ['class' => 'red']]),
            $this->getContainer()->html->fefselect->option( '-1', Text::_('SOLO_CONFIG_DISPLAY_DARKMODE_OPT_AUTO'), ['attr' => ['class' => 'orange']]),
            $this->getContainer()->html->fefselect->option( '1', Text::_('AWF_YES'), ['attr' => ['class' => 'green']]),
        ], 'darkmode', ['forToggle' => 1], 'value', 'text', (int) $config->get('darkmode', -1), 'darkmode'); ?>
    </div>
    <p class="akeeba-help-text">
        <?php echo $this->getLanguage()->text('SOLO_CONFIG_DISPLAY_DARKMODE_DESCRIPTION'); ?>
    </p>
</div>

<?php if(defined('WPINC')): ?>
    <div class="akeeba-form-group">
        <label for="under_tools">
            <?php echo $this->getLanguage()->text('SOLO_CONFIG_UNDER_TOOLS_LABEL'); ?>
        </label>
        <div class="akeeba-toggle">
            <?php echo $this->getContainer()->html->get('fefselect.booleanList', 'under_tools', array('forToggle' => 1, 'colorBoolean' => 1), $config->get('under_tools', 0)); ?>
        </div>
        <p class="akeeba-help-text">
            <?php echo $this->getLanguage()->text('SOLO_CONFIG_UNDER_TOOLS_DESCRIPTION'); ?>
        </p>
    </div>
<?php endif; ?>

<div class="akeeba-form-group">
    <label for="useencryption">
        <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_SECURITY_USEENCRYPTION_LABEL'); ?>
    </label>
    <div class="akeeba-toggle">
        <?php echo $this->getContainer()->html->get('fefselect.booleanList', 'useencryption', array('forToggle' => 1, 'colorBoolean' => 1), $config->get('useencryption', 1)); ?>
    </div>
    <p class="akeeba-help-text">
        <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_SECURITY_USEENCRYPTION_DESCRIPTION'); ?>
    </p>
</div>

<div class="akeeba-form-group">
    <label for="no_flush">
        <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_SECURITY_NO_FLUSH_LABEL'); ?>
    </label>
    <div class="akeeba-toggle">
        <?php echo $this->getContainer()->html->get('fefselect.booleanList', 'no_flush', array('forToggle' => 1, 'colorBoolean' => 1), $config->get('no_flush', 0)); ?>
    </div>
    <p class="akeeba-help-text">
        <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_SECURITY_NO_FLUSH_DESCRIPTION'); ?>
    </p>
</div>

<?php
// WordPress sets its own timezone. We use that value forcibly in our WP-specific Solo\Application\AppConfig (helpers/Solo/Application/AppConfig.php). Therefore we display it locked in WP. ?>
<div class="akeeba-form-group">
    <label for="timezone">
        <?php if($inCMS): ?>
            <?php echo $this->getLanguage()->text('SOLO_SETUP_LBL_TIMEZONE_WP'); ?>
        <?php else: ?>
            <?php echo $this->getLanguage()->text('SOLO_SETUP_LBL_TIMEZONE'); ?>
        <?php endif; ?>
    </label>
    <?php echo $this->getContainer()->html->setup->timezoneSelect($timezone, 'timezone', true, $inCMS); ?>

    <p class="akeeba-help-text">
        <?php echo $this->getLanguage()->text($inCMS ? 'SOLO_SETUP_LBL_TIMEZONE_WORDPRESS' : 'SOLO_SETUP_LBL_TIMEZONE_HELP'); ?>
    </p>
</div>

<div class="akeeba-form-group">
    <label for="localtime">
        <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_BACKEND_LOCALTIME_LABEL'); ?>
    </label>
    <div class="akeeba-toggle">
        <?php echo $this->getContainer()->html->get('fefselect.booleanList', 'localtime', array('forToggle' => 1, 'colorBoolean' => 1), $config->get('localtime', 1)); ?>
    </div>
    <p class="akeeba-help-text">
        <?php if($inCMS): ?>
            <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_BACKEND_LOCALTIME_WP_DESC'); ?>
        <?php else: ?>
            <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_BACKEND_LOCALTIME_DESC'); ?>
        <?php endif; ?>
    </p>
</div>

<div class="akeeba-form-group">
    <label for="timezonetext">
        <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_BACKEND_TIMEZONETEXT_LABEL'); ?>
    </label>
    <?php echo $this->getContainer()->html->setup->timezoneFormatSelect($config->get('timezonetext', 'T')); ?>

    <p class="akeeba-help-text">
        <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_BACKEND_TIMEZONETEXT_DESC'); ?>
    </p>
</div>

<div class="akeeba-form-group">
    <label for="forced_backup_timezone">
        <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_FORCEDBACKUPTZ_LABEL'); ?>
    </label>
    <?php echo $this->getContainer()->html->setup->timezoneSelect($config->get('forced_backup_timezone', 'AKEEBA/DEFAULT'), 'forced_backup_timezone', true); ?>

    <p class="akeeba-help-text">
        <?php if($inCMS): ?>
            <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_FORCEDBACKUPTZ_WP_DESC'); ?>
        <?php else: ?>
            <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_FORCEDBACKUPTZ_DESC'); ?>
        <?php endif; ?>
    </p>
</div>

<?php if($inCMS && AKEEBABACKUP_PRO): ?>
    <div class="akeeba-form-group">
        <label for="wp_cron_override_time">
            <?php echo $this->getLanguage()->text('SOLO_CONFIG_WP_CRON_OVERRIDE_TIME_LABEL'); ?>
        </label>
        <?php echo $this->getContainer()->html->get('fefselect.genericlist', [
            $this->getContainer()->html->fefselect->option( '0', Text::_('AWF_NO')),
            $this->getContainer()->html->fefselect->option( '1', Text::_('SOLO_CONFIG_WP_CRON_OVERRIDE_TIME_OPTIMISTIC')),
            $this->getContainer()->html->fefselect->option( '2', Text::_('SOLO_CONFIG_WP_CRON_OVERRIDE_TIME_CONSERVATIVE')),
        ], 'wp_cron_override_time', [], 'value', 'text', (int) $config->get('wp_cron_override_time', 1),'wp_cron_override_time'); ?>
        <p class="akeeba-help-text">
            <?php echo $this->getLanguage()->text('SOLO_CONFIG_WP_CRON_OVERRIDE_TIME_DESCRIPTION'); ?>
        </p>
    </div>
<?php endif; ?>

<div class="akeeba-form-group">
    <label for="showDeleteOnRestore">
        <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_BACKEND_SHOWDELETEONRESTORE_LABEL'); ?>
    </label>
    <div class="akeeba-toggle">
        <?php echo $this->getContainer()->html->get('fefselect.booleanList', 'showDeleteOnRestore', array('forToggle' => 1, 'colorBoolean' => 1), $config->get('showDeleteOnRestore', 0)); ?>
    </div>
    <p class="akeeba-help-text">
        <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_BACKEND_SHOWDELETEONRESTORE_DESC'); ?>
    </p>
</div>

<?php if($inCMS): ?>
    <div class="akeeba-form-group">
        <label for="showBrowserDownload">
            <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_BACKEND_SHOWBROWSERDOWNLOAD_LABEL'); ?>
        </label>
        <div class="akeeba-toggle">
            <?php echo $this->getContainer()->html->get('fefselect.booleanList', 'showBrowserDownload', array('forToggle' => 1, 'colorBoolean' => 1), $config->get('showBrowserDownload', 0)); ?>
        </div>
        <p class="akeeba-help-text">
            <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_BACKEND_SHOWBROWSERDOWNLOAD_DESC'); ?>
        </p>
    </div>
<?php endif; ?>

<?php if(!$inCMS): ?>

    <div class="akeeba-form-group">
        <label for="live_site">
            <?php echo $this->getLanguage()->text('SOLO_SETUP_LBL_LIVESITE'); ?>
        </label>
        <input type="text" name="live_site" id="live_site"
               value="<?php echo $config->get('live_site'); ?>">
        <p class="akeeba-help-text">
            <?php echo $this->getLanguage()->text('SOLO_SETUP_LBL_LIVESITE_HELP'); ?>
        </p>
    </div>

    <div class="akeeba-form-group">
        <label for="session_timeout">
            <?php echo $this->getLanguage()->text('SOLO_SETUP_LBL_SESSIONTIMEOUT'); ?>
        </label>
        <input type="text" name="session_timeout" id="session_timeout"
               value="<?php echo $config->get('session_timeout'); ?>">
        <p class="akeeba-help-text">
            <?php echo $this->getLanguage()->text('SOLO_SETUP_LBL_SESSIONTIMEOUT_HELP'); ?>
        </p>
    </div>
<?php endif; ?>

<div class="akeeba-form-group">
    <label for="dateformat">
        <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_DATEFORMAT_LABEL'); ?>
    </label>
    <input type="text" name="dateformat" id="dateformat"
           value="<?php echo $config->get('dateformat'); ?>">
    <p class="akeeba-help-text">
        <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_DATEFORMAT_DESC'); ?>
    </p>
</div>

<div class="akeeba-form-group">
    <label for="stats_enabled">
        <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_USAGESTATS_SOLO_LABEL'); ?>
    </label>
    <div class="akeeba-toggle">
        <?php echo $this->getContainer()->html->get('fefselect.booleanList', 'stats_enabled', array('forToggle' => 1, 'colorBoolean' => 1), $config->get('stats_enabled', 1)); ?>
    </div>
    <p class="akeeba-help-text">
        <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_USAGESTATS_SOLO_DESC'); ?>
    </p>
</div>

<div class="akeeba-form-group">
    <label for="accurate_php_cli">
        <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_ACCURATE_PHP_CLI_LABEL'); ?>
    </label>
    <div class="akeeba-toggle">
        <?php echo $this->getContainer()->html->get('fefselect.booleanList', 'accurate_php_cli', array('forToggle' => 1, 'colorBoolean' => 1), $config->get('accurate_php_cli', 1)); ?>
    </div>
    <p class="akeeba-help-text">
        <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_ACCURATE_PHP_CLI_DESC'); ?>
    </p>
</div>

<?php if(!$inCMS): ?>

    <div class="akeeba-form-group">
        <label for="proxy_host">
            <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_PROXY_HOST_LABEL'); ?>
        </label>
        <input type="text" name="proxy_host" id="proxy_host"
               value="<?php echo $config->get('proxy_host'); ?>">
        <p class="akeeba-help-text">
            <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_PROXY_HOST_DESC'); ?>
        </p>
    </div>

    <div class="akeeba-form-group" <?php echo $this->showOn('proxy_host!:'); ?>>
        <label for="proxy_port">
            <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_PROXY_PORT_LABEL'); ?>
        </label>
        <input type="number" min="1" max="65535" name="proxy_port" id="proxy_port"
               value="<?php echo $config->get('proxy_port', '8080'); ?>">
        <p class="akeeba-help-text">
            <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_PROXY_PORT_DESC'); ?>
        </p>
    </div>

    <div class="akeeba-form-group" <?php echo $this->showOn('proxy_host!:'); ?>>
        <label for="proxy_user">
            <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_PROXY_USER_LABEL'); ?>
        </label>
        <input type="text" name="proxy_user" id="proxy_user"
               value="<?php echo $config->get('proxy_user', ''); ?>">
        <p class="akeeba-help-text">
            <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_PROXY_USER_DESC'); ?>
        </p>
    </div>

    <div class="akeeba-form-group" <?php echo $this->showOn('proxy_host!:'); ?>>
        <label for="proxy_pass">
            <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_PROXY_PASS_LABEL'); ?>
        </label>
        <input type="password" name="proxy_pass" id="proxy_pass"
               value="<?php echo $config->get('proxy_pass', ''); ?>">
        <p class="akeeba-help-text">
            <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_PROXY_PASS_DESC'); ?>
        </p>
    </div>
<?php endif; ?>

<hr />

<div class="akeeba-form-group">
    <label for="fs_driver">
        <?php echo $this->getLanguage()->text('SOLO_SETUP_LBL_FS_DRIVER'); ?>
    </label>
    <?php echo $this->getContainer()->html->setup->fsDriverSelect( $config->get('fs.driver')); ?>

    <p class="akeeba-help-text">
        <?php echo $this->getLanguage()->text('SOLO_SETUP_LBL_FS_DRIVER_HELP'); ?>
    </p>
</div>

<div id="ftp_options" <?php echo $this->showOn('fs_driver!:file'); ?>>
    <div class="akeeba-form-group">
        <label for="fs_host">
            <?php echo $this->getLanguage()->text('SOLO_SETUP_LBL_FS_FTP_HOST'); ?>
        </label>
        <input type="text" name="fs_host" id="fs_host"
               value="<?php echo $config->get('fs.host'); ?>">
        <p class="akeeba-help-text">
            <?php echo $this->getLanguage()->text('SOLO_SETUP_LBL_FS_FTP_HOST_HELP'); ?>
        </p>
    </div>

    <div class="akeeba-form-group">
        <label for="fs_port">
            <?php echo $this->getLanguage()->text('SOLO_SETUP_LBL_FS_FTP_PORT'); ?>
        </label>
        <input type="text" name="fs_port" id="fs_port"
               value="<?php echo $config->get('fs.port'); ?>">
        <p class="akeeba-help-text">
            <?php echo $this->getLanguage()->text('SOLO_SETUP_LBL_FS_FTP_PORT_HELP'); ?>
        </p>
    </div>

    <div class="akeeba-form-group">
        <label for="fs_username">
            <?php echo $this->getLanguage()->text('SOLO_SETUP_LBL_FS_FTP_USERNAME'); ?>
        </label>
        <input type="text" name="fs_username" id="fs_username"
               value="<?php echo $config->get('fs.username'); ?>">
        <p class="akeeba-help-text">
            <?php echo $this->getLanguage()->text('SOLO_SETUP_LBL_FS_FTP_USERNAME_HELP'); ?>
        </p>
    </div>

    <div class="akeeba-form-group">
        <label for="fs_password">
            <?php echo $this->getLanguage()->text('SOLO_SETUP_LBL_FS_FTP_PASSWORD'); ?>
        </label>
        <input type="password" name="fs_password" id="fs_password"
               value="<?php echo $config->get('fs.password'); ?>">
        <p class="akeeba-help-text">
            <?php echo $this->getLanguage()->text('SOLO_SETUP_LBL_FS_FTP_PASSWORD_HELP'); ?>
        </p>
    </div>

    <div class="akeeba-form-group">
        <label for="fs_directory">
            <?php echo $this->getLanguage()->text('SOLO_SETUP_LBL_FS_FTP_DIRECTORY'); ?>
        </label>

        <input type="text" name="fs_directory" id="fs_directory" value="<?php echo $config->get('fs.directory'); ?>" />

        <p class="akeeba-help-text">
            <?php echo $this->getLanguage()->text('SOLO_SETUP_LBL_FS_FTP_DIRECTORY_HELP'); ?>
        </p>
    </div>
</div>
