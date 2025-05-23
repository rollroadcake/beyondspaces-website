<?php /* /var/www/html/wp-content/plugins/akeebabackupwp/app/Solo/ViewTemplates/Sysconfig/push.blade.php */ ?>
<?php
/**
 * @package   solo
 * @copyright Copyright (c)2014-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_AKEEBA') or die();

/** @var \Solo\View\Sysconfig\Html $this */

$config = $this->getContainer()->appConfig;

/**
 * Remember to update wpcli/Command/Sysconfig.php in the WordPress application whenever this file changes.
 */
?>
<div class="akeeba-form-group">
    <label for="desktop_notifications">
        <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_DESKTOP_NOTIFICATIONS_LABEL'); ?>
    </label>
    <div class="akeeba-toggle">
        <?php echo $this->getContainer()->html->get('fefselect.booleanList', 'options[desktop_notifications]', ['id' => 'desktop_notifications', 'forToggle' => 1, 'colorBoolean' => 1], $config->get('options.desktop_notifications', 0)); ?>
    </div>
    <p class="akeeba-help-text">
        <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_DESKTOP_NOTIFICATIONS_DESC'); ?>
    </p>
</div>

<div class="akeeba-form-group">
    <label for="push_preference">
        <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_PUSH_PREFERENCE_LABEL'); ?>
    </label>
    <div class="akeeba-toggle">
        <?php echo $this->getContainer()->html->get('fefselect.booleanList', 'options[push_preference]', ['id' => 'push_preference', 'forToggle' => 1, 'colorBoolean' => 1], $config->get('options.push_preference', 0)); ?>
    </div>
    <p class="akeeba-help-text">
        <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_PUSH_PREFERENCE_DESC'); ?>
    </p>
</div>

<div class="akeeba-form-group" <?php echo $this->showOn('options[push_preference]:1'); ?>>
    <label for="push_apikey">
        <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_PUSH_APIKEY_LABEL'); ?>
    </label>
    <input type="text" name="options[push_apikey]" id="push_apikey"
           placeholder="<?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_PUSH_APIKEY_LABEL'); ?>"
           value="<?php echo $config->get('options.push_apikey'); ?>">
    <p class="akeeba-help-text">
        <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_PUSH_APIKEY_DESC'); ?>
    </p>
</div>
