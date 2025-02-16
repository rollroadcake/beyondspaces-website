<?php /* /var/www/html/wp-content/plugins/akeebabackupwp/app/Solo/ViewTemplates/Log/default.blade.php */ ?>
<?php
/**
 * @package   solo
 * @copyright Copyright (c)2014-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_AKEEBA') or die();

// Used for type hinting
/** @var  \Solo\View\Log\Html $this */

?>
<?php if(isset($this->logs) && count($this->logs)): ?>
    <form name="adminForm" id="adminForm" action="<?php echo $this->container->router->route('index.php?view=Log'); ?>" method="POST"
          class="akeeba-form--inline">
        <div class="akeeba-form-group">
            <label for="tag">
                <?php echo $this->getLanguage()->text('COM_AKEEBA_LOG_CHOOSE_FILE_TITLE'); ?>
            </label>
            <?php echo $this->getContainer()->html->get('select.genericList', $this->logs, 'tag', ['list.attr' => ['class' => 'akeebaGridViewAutoSubmitOnChange'], 'list.select' => $this->tag, 'id' => 'tag']); ?>
        </div>

        <?php if(!empty($this->tag)): ?>
            <div class="akeeba-form-group--actions">
                <a class="akeeba-btn--primary"
                   href="<?php echo $this->container->router->route('index.php?view=Log&task=download&format=raw&tag=' . urlencode($this->tag)); ?>">
                    <span class="akion-ios-download"></span>
                    <?php echo $this->getLanguage()->text('COM_AKEEBA_LOG_LABEL_DOWNLOAD'); ?>
                </a>

            </div>
        <?php endif; ?>

        <input type="hidden" name="token" value="<?php echo $this->container->session->getCsrfToken()->getValue(); ?>">
    </form>
<?php endif; ?>

<?php if(!empty($this->tag)): ?>
    <?php if($this->logTooBig): ?>
        <div class="akeeba-block--warning">
            <p>
                <?php echo $this->getLanguage()->sprintf('COM_AKEEBA_LOG_SIZE_WARNING', number_format($this->logSize / (1024 * 1024), 2)); ?>
            </p>
            <button class="akeeba-btn--dark" id="showlog">
                <?php echo $this->getLanguage()->text('COM_AKEEBA_LOG_SHOW_LOG'); ?>
            </button>
        </div>
    <?php endif; ?>

    <div id="iframe-holder" class="akeeba-panel--primary" style="display: <?php echo $this->logTooBig ? 'none' : 'block'; ?>;">
        <?php if(!$this->logTooBig): ?>
            <iframe
                    src="<?php echo $this->container->router->route('index.php?view=Log&task=iframe&format=raw&tag=' . urlencode($this->tag)); ?>"
                    width="99%" height="400px">
            </iframe>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if( ! (isset($this->logs) && count($this->logs))): ?>
    <div class="alert alert-danger">
        <?php echo $this->getLanguage()->text('COM_AKEEBA_LOG_NONE_FOUND'); ?>
    </div>
<?php endif; ?>
