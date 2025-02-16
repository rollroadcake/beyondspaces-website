<?php /* /var/www/html/wp-content/plugins/akeebabackupwp/app/Solo/ViewTemplates/Wizard/wizard.blade.php */ ?>
<?php
/**
 * @package   solo
 * @copyright Copyright (c)2014-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Akeeba\Engine\Factory;

defined('_AKEEBA') or die();

/** @var \Solo\View\Wizard\Html $this */

$config = Factory::getConfiguration();

$steps = ['flush', 'minexec', 'directory', 'dbopt', 'maxexec', 'splitsize']

?>

<div id="akeeba-confwiz">
	<div id="backup-progress-pane" style="display: none">
		<div class="akeeba-block--warning">
			<?php echo $this->getLanguage()->text('COM_AKEEBA_CONFWIZ_INTROTEXT'); ?>
		</div>

		<div id="backup-progress-header" class="akeeba-panel--info">
            <header class="akeeba-block-header">
                <h3>
                    <?php echo $this->getLanguage()->text('COM_AKEEBA_CONFWIZ_PROGRESS'); ?>
                </h3>
            </header>

            <div id="backup-progress-content">
				<div id="confwiz-steps">
					<?php foreach ($steps as $step): ?>
					<div id="step-<?php echo $step; ?>" class="confwiz-step">
						<span id="step-<?php echo $step; ?>-wait">
							<span class="akion akion-clock"></span>
						</span>
						<span id="step-<?php echo $step; ?>-run" style="display: none">
							<span class="akion akion-play"></span>
						</span>
						<span id="step-<?php echo $step; ?>-done" style="display: none">
							<span class="akion akion-checkmark"></span>
						</span>
						<span id="step-<?php echo $step; ?>-error" style="display: none">
							<span class="akion akion-stop"></span>
						</span>
						<span><?php echo $this->getLanguage()->text('COM_AKEEBA_CONFWIZ_' . $step); ?></span>
					</div>
					<?php endforeach; ?>
				</div>
				<div class="backup-steps-container">
					<div id="backup-substep">
					</div>
				</div>
			</div>
			<span id="ajax-worker"></span>
		</div>

	</div>

	<div id="error-panel" class="akeeba-block--failure" style="display:none">
		<h3 class="alert-heading"><?php echo $this->getLanguage()->text('COM_AKEEBA_CONFWIZ_HEADER_FAILED'); ?></h3>
		<div id="errorframe">
			<p id="backup-error-message">
			</p>
		</div>
	</div>

	<div id="backup-complete" style="display: none">
		<div class="akeeba-block--success">
			<h2 class="alert-heading"><?php echo $this->getLanguage()->text('COM_AKEEBA_CONFWIZ_HEADER_FINISHED'); ?></h2>
			<div id="finishedframe">
				<p>
					<?php echo $this->getLanguage()->text('COM_AKEEBA_CONFWIZ_CONGRATS'); ?>
				</p>
			</div>

            <a class="akeeba-btn--primary--big" href="<?php echo $this->container->router->route('index.php?&view=backup'); ?>">
				<span class="akion-play"></span>
				<?php echo $this->getLanguage()->text('COM_AKEEBA_BACKUP'); ?>
			</a>

            <a class="akeeba-btn--ghost" href="<?php echo $this->container->router->route('index.php?&view=configuration'); ?>">
				<span class="akion-wrench"></span>
				<?php echo $this->getLanguage()->text('COM_AKEEBA_CONFIG'); ?>
			</a>

			<?php if(AKEEBABACKUP_PRO): ?>
            <a class="akeeba-btn--ghost" href="<?php echo $this->container->router->route('index.php?&view=schedule'); ?>">
				<span class="akion-calendar"></span>
				<?php echo $this->getLanguage()->text('COM_AKEEBA_SCHEDULE'); ?>
			</a>
			<?php endif; ?>
		</div>

	</div>
</div>
