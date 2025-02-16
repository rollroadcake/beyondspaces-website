<?php /* /var/www/html/wp-content/plugins/akeebabackupwp/app/Solo/ViewTemplates/Sysconfig/publicapi.blade.php */ ?>
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
    <label for="legacyapi_enabled">
        <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_LEGACYAPI_ENABLED_LABEL'); ?>
    </label>
    <div class="akeeba-toggle">
        <?php echo $this->getContainer()->html->get('fefselect.booleanList', 'options[legacyapi_enabled]', ['id' => 'legacyapi_enabled', 'forToggle' => 1, 'colorBoolean' => 1], $config->get('options.legacyapi_enabled', 0)); ?>
    </div>
    <p class="akeeba-help-text">
        <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_LEGACYAPI_ENABLED_DESC'); ?>
    </p>
</div>

<div class="akeeba-form-group">
    <label for="jsonapi_enabled">
        <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_JSONAPI_ENABLED_LABEL'); ?>
    </label>
    <div class="akeeba-toggle">
        <?php echo $this->getContainer()->html->get('fefselect.booleanList', 'options[jsonapi_enabled]', ['id' => 'jsonapi_enabled', 'forToggle' => 1, 'colorBoolean' => 1], $config->get('options.jsonapi_enabled', 0)); ?>
    </div>
    <p class="akeeba-help-text">
        <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_JSONAPI_ENABLED_DESC'); ?>
    </p>
</div>

<div class="akeeba-form-group" <?php echo $this->showOn('options[legacyapi_enabled]:1[OR]options[jsonapi_enabled]:1'); ?>>
    <label for="frontend_secret_word">
        <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_SECRETWORD_LABEL'); ?>
    </label>
    <input type="text" name="options[frontend_secret_word]" id="frontend_secret_word"
           placeholder="<?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_SECRETWORD_LABEL'); ?>"
           value="<?php echo \Akeeba\Engine\Platform::getInstance()->get_platform_configuration_option('frontend_secret_word', ''); ?>">
    <p class="akeeba-help-text">
        <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_SECRETWORD_DESC'); ?>
    </p>
</div>

<div class="akeeba-form-group">
    <label for="frontend_email_on_finish">
        <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_FRONTENDEMAIL_LABEL'); ?>
    </label>
    <div class="akeeba-toggle">
        <?php echo $this->getContainer()->html->get('fefselect.booleanList', 'options[frontend_email_on_finish]', ['id' => 'frontend_email_on_finish', 'forToggle' => 1, 'colorBoolean' => 1], $config->get('options.frontend_email_on_finish', 0)); ?>
    </div>
    <p class="akeeba-help-text">
        <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_FRONTENDEMAIL_DESC'); ?>
    </p>
</div>

<div class="akeeba-form-group" <?php echo $this->showOn('options[frontend_email_on_finish]:1'); ?>>
    <label for="frontend_email_when">
        <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_FRONTEND_EMAIL_WHEN_LABEL'); ?>
    </label>
    <div class="akeeba-toggle">
        <?php echo $this->getContainer()->html->get('fefselect.genericlist', 			[
				'always'       => 'COM_AKEEBA_CONFIG_FRONTEND_EMAIL_WHEN_ALWAYS',
				'failedupload' => 'COM_AKEEBA_CONFIG_FRONTEND_EMAIL_WHEN_FAILEDUPLOAD',
			], 'options[frontend_email_when]',
			[], 'value', 'text',
			$config->get('options.frontend_email_when', 'always'), 'frontend_email_when', true
        ); ?>
    </div>
    <p class="akeeba-help-text">
        <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_FRONTEND_EMAIL_WHEN_DESC'); ?>
    </p>
</div>

<div class="akeeba-form-group" <?php echo $this->showOn('options[frontend_email_on_finish]:1'); ?>>
    <label for="frontend_email_address">
        <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_ARBITRARYFEEMAIL_LABEL'); ?>
    </label>
    <input type="email" name="options[frontend_email_address]" id="frontend_email_address"
           placeholder="<?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_ARBITRARYFEEMAIL_LABEL'); ?>"
           value="<?php echo $config->get('options.frontend_email_address'); ?>">
    <p class="akeeba-help-text">
        <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_ARBITRARYFEEMAIL_DESC'); ?>
    </p>
</div>

<div class="akeeba-form-group" <?php echo $this->showOn('options[frontend_email_on_finish]:1'); ?>>
    <label for="frontend_email_subject">
        <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_FEEMAILSUBJECT_LABEL'); ?>
    </label>
    <input type="text" name="options[frontend_email_subject]" id="frontend_email_subject"
           placeholder="<?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_FEEMAILSUBJECT_DESC'); ?>"
           value="<?php echo $config->get('options.frontend_email_subject'); ?>">
    <p class="akeeba-help-text">
        <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_FEEMAILSUBJECT_DESC'); ?>
    </p>
</div>

<div class="akeeba-form-group" <?php echo $this->showOn('options[frontend_email_on_finish]:1'); ?>>
    <label for="frontend_email_body">
        <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_FEEMAILBODY_LABEL'); ?>
    </label>
    <textarea rows="10" name="options[frontend_email_body]"
              id="frontend_email_body"><?php echo $config->get('options.frontend_email_body'); ?></textarea>
    <p class="akeeba-help-text">
        <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG_FEEMAILBODY_DESC'); ?>
    </p>
</div>
