<?php
/**
 * @package   solo
 * @copyright Copyright (c)2014-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Awf\Exception\App as AWFAppException;
use Awf\Registry\Registry;
use Awf\Text\Text;
use Solo\Exception\Update\ConnectionError;
use Solo\Exception\Update\PlatformError;
use Solo\Exception\Update\StabilityError;
use Solo\Exception\Update\UpdateError;

/**
 * Integration with WordPress' Plugins Updater.
 *
 * We convey the update information of our plugin to WordPress. The extent of WordPress' involvement in the update
 * installation is that of a glorified `wget` and `unzip` combo.
 *
 * WE CAN NOT AND DO NOT TRUST WORDPRESS TO RUN POST-UPGRADE CODE.
 *
 * Instead, the post-upgrade code runs “magically” whenever we call `\AkeebaBackupWP::loadAkeebaBackupContainer`.
 */
abstract class AkeebaBackupWPUpdater
{
	/**
	 * Private static variable keys that belong to our frozen state, stored in a site transient.
	 */
	const STATE_KEYS = [
		'needsDownloadID',
		'connectionError',
		'platformError',
		'downloadLink',
		'cantUseWpUpdate',
		'stabilityError',
	];

	/** @var bool Do I need the Download ID? */
	protected static $needsDownloadID = false;

	/** @var bool Did I have a connection error while */
	protected static $connectionError = false;

	/** @var bool Do I have a platform error? (Wrong PHP or WP version) */
	protected static $platformError = false;

	/** @var string    Stores the download link. In this way we can run our logic only on our download links */
	protected static $downloadLink;

	/** @var bool    Am I in an ancient version of WordPress, were the integrated system is not usable? */
	protected static $cantUseWpUpdate = false;

	/** @var bool    Do I have an update that's less stable than my preferred stability? */
	protected static $stabilityError = false;

	/**
	 * Force WordPress to reload update information
	 *
	 * @return  void
	 * @since   8.1.0
	 */
	public static function forceReload(): void
	{
		delete_transient('update_plugins');
		delete_transient('akeebabackupwp_pluginupdate_frozenstate');
	}

	/**
	 * Report update information to WordPress.
	 *
	 * Handles the `pre_set_site_transient_update_plugins` filter
	 *
	 * Retrieve the update information from Akeeba Backup for WordPress' update cache and report them back to WordPress
	 * in a format it understands.
	 *
	 * The returned information is cached by WordPress and used by checkinfo() to render the Akeeba Backup for WordPress
	 * update information in WordPress' Plugins page.
	 *
	 * DO NOT TYPE HINT!
	 *
	 * @param   stdClass  $value
	 * @param   string    $transientName
	 *
	 * @return  stdClass
	 * @throws  AWFAppException
	 * @see     https://developer.wordpress.org/reference/hooks/pre_set_site_transient_transient/
	 */
	public static function getUpdateInformation($value = null, $transientName = null)
	{
		global $wp_version;
		global $akeebaBackupWordPressLoadPlatform;

		if ($transientName !== 'update_plugins' || empty($value) || !is_object($value)
		    || (is_multisite()
		        && !is_network_admin()))
		{
			return $value;
		}

		/**
		 * On WordPress < 4.3 we can't use the integrated update system since the hook we're using to apply our Download
		 * ID is not available. Instead, we warn the user and tell them to use our own update system.
		 */
		if (version_compare($wp_version, '4.3', 'lt'))
		{
			static::$cantUseWpUpdate = true;
			self::freezeState();

			return $value;
		}

		/**
		 * When the plugin is deleted, Wordpress reloads the updates. Since this file was already in memory, its code
		 * runs even if Akeeba Backup's files are not installed anymore. The following is a sanity check to prevent
		 * a PHP fatal error during the uninstallation of the plugin.
		 */
		$akeebaBackupWordPressLoadPlatform = false;

		if (!file_exists(__DIR__ . '/../helpers/integration.php'))
		{
			self::freezeState();

			return $value;
		}

		// Do I have to notify the user that the Download ID is missing?
		if (static::needsDownloadID())
		{
			static::$needsDownloadID = true;
		}

		$updateInfo = false;

		try
		{
			$updateInfo = static::getUpdateInfo();
		}
		catch (ConnectionError $e)
		{
			// mhm... an error occurred while connecting to the updates server. Let's notify the user
			static::$connectionError = true;
		}
		catch (PlatformError $e)
		{
			static::$platformError = true;
		}
		catch (StabilityError $e)
		{
			static::$stabilityError = true;
		}
		catch (AWFAppException $e)
		{
			static::$connectionError = true;
		}

		self::freezeState();

		if (!$updateInfo)
		{
			return $value;
		}

		if (!$value || !isset($value->response))
		{
			// Double check that we actually have an object to interact with. Since the $transient data is pulled from the database
			// and could be manipulated by other plugins, we might have an unexpected value here
			if (!$value)
			{
				$value = new stdClass();
			}

			$value->response = [];
		}

		$dirSlug = self::getPluginSlug();

		$obj              = new stdClass();
		$obj->slug        = $dirSlug;
		$obj->plugin      = $dirSlug . '/akeebabackupwp.php';
		$obj->new_version = $updateInfo->get('version');
		$obj->url         = $updateInfo->get('infoUrl');
		$obj->package     = $updateInfo->get('download');
		$obj->icons       = [
			'2x' => WP_PLUGIN_URL . '/' . AkeebaBackupWP::$dirName . '/app/media/logo/abwp-256.png',
			'1x' => WP_PLUGIN_URL . '/' . AkeebaBackupWP::$dirName . '/app/media/logo/abwp-128.png',
		];

		if ($updateInfo->get('hasUpdate', false))
		{
			$value->response                                   = $value->response ?? [];
			$value->response[$dirSlug . '/akeebabackupwp.php'] = $obj;
		}
		else
		{
			$value->no_update                                   = $value->no_update ?? [];
			$value->no_update[$dirSlug . '/akeebabackupwp.php'] = $obj;
		}

		/**
		 * Since the event we're hooking to is a global one (triggered for every plugin) we have to store a reference
		 * of our download link. This way we can apply our logic only on our stuff and don't interfere with third party
		 * plugins.
		 */
		static::$downloadLink = $updateInfo->get('link');

		return $value;
	}

	/**
	 * Used to render "View version x.x.x details" link from the plugins page.
	 *
	 * Handles the `plugins_api` filter.
	 *
	 * We hook to this event to redirect the connection from the WordPress directory to our site for updates
	 *
	 * DO NOT TYPE HINT!
	 *
	 * @param   false|object|array  $result  The result object or array. Default false.
	 * @param   string              $action  The type of information being requested from the Plugin Installation API.
	 * @param   object|array        $arg     Plugin API arguments.
	 *
	 * @return  false|object|array
	 *
	 * @see https://developer.wordpress.org/reference/hooks/plugins_api/
	 */
	public static function pluginInformationPage($result = false, $action = '', $arg = null)
	{
		if (!in_array($action ?: '', ['query_plugins', 'plugin_information']))
		{
			return $result;
		}

		if (!is_object($arg))
		{
			return $result;
		}

		$dirSlug = self::getPluginSlug();

		if (!isset($arg->slug))
		{
			return $result;
		}

		if ($arg->slug !== $dirSlug)
		{
			return $result;
		}

		try
		{
			$updateInfo = static::getUpdateInfo();
		}
		catch (UpdateError $e)
		{
			$updateInfo = false;
		}
		catch (AWFAppException $e)
		{
			$updateInfo = false;
		}

		/**
		 * Sanity check.
		 *
		 * This if-block should never be triggered. We only ever reach this code if we have already determined there is
		 * an update available.
		 */
		if (!$updateInfo)
		{
			return $result;
		}

		$platform         = function_exists('classicpress_version') ? 'classicpress' : 'wordpress';
		$platforms        = $updateInfo->get('platforms');
		$platformVersions = $platforms->{$platform} ?? [];
		$minPlatformVersion = array_reduce(
			$platformVersions,
			fn($carry, $version) => empty($carry)
				? str_replace('+', '', $version)
				: (
					version_compare($carry, str_replace('+', '', $version), 'gt')
						? str_replace('+', '', $version)
						: $carry
				),
			''
		);
		$minPlatformVersion = $minPlatformVersion ?: get_bloginfo('version');

		$minPhp = array_reduce(
			$updateInfo->get('php', []) ?: [],
			fn ($carry, $version) => empty($carry)
				? $version
				: (version_compare($carry, $version, 'lt') ? $carry : $version),
			''
		);

		$releaseNotes = $updateInfo->get(
			'releaseNotes',
			sprintf(
				"For downloads and release notes please visit the <a href='%s' target='_blank'>the download page of version %s</a> on the plugin's site.",
				$updateInfo->get('infoUrl'),
				$updateInfo->get('version')
			)
		);

		/**
		 * This is the information WordPress is using to render the Akeeba Backup for WordPress row in its Plugins page.
		 */
		$information = [
			// We leave the "name" index empty, so WordPress won't display the ugly title on top of our banner
			'name'          => '',
			'slug'          => $dirSlug,
			'author'        => 'Akeeba Ltd.',
			'homepage'      => 'https://www.akeeba.com/products/akeeba-backup-wordpress.html',
			'last_updated'  => $updateInfo->get('date'),
			'version'       => $updateInfo->get('version'),
			'download_link' => $updateInfo->get('download'),
			'requires'      => $minPlatformVersion,
			'requires_php'  => $minPhp,
			//'tested'        => get_bloginfo('version'),
			'sections'      => [
				'release_notes' => $releaseNotes,
			],
			'banners' => [
				'low'  => plugins_url() . '/' . $dirSlug . '/app/media/image/wordpressupdate_banner.jpg',
				'high' => false,
			],
		];

		return (object) $information;
	}

	/**
	 * Throws an error if the Download ID is missing when the user tries to install an update.
	 *
	 * Handles the `upgrader_pre_download` filter.
	 *
	 * DO NOT TYPE HINT!
	 *
	 * @param   bool              $bailout
	 * @param   null|string       $package
	 * @param   null|WP_Upgrader  $upgrader
	 *
	 * @return WP_Error|false    An error if anything goes wrong or is missing, either case FALSE to keep the update
	 *                           process going
	 * @see https://developer.wordpress.org/reference/hooks/upgrader_pre_download/
	 */
	public static function addDownloadID($bailout = false, $package = null, $upgrader = null)
	{
		if (!is_string($package))
		{
			return false;
		}

		// Process only our download links
		if ($package != static::$downloadLink)
		{
			return false;
		}

		// Do we need the Download ID (ie Pro version)?
		if (static::needsDownloadID())
		{
			return new WP_Error(
				403, 'Please insert your Download ID inside Akeeba Backup to fetch the updates for the Pro version'
			);
		}

		// Our updater automatically sets the Download ID in the link, so there's no need to change anything inside the URL
		return false;
	}

	/**
	 * Helper function to display some custom text AFTER the row regarding our update.
	 *
	 * Handles the `after_plugin_row_akeebaebackupwp/akeebabackupwp.php` filter
	 *
	 * This is typically used to communicate problems preventing the update information from being retrieved or used,
	 * meaning updates for our plugin are essentially broken.
	 *
	 * DO NOT TYPEHINT!
	 *
	 * @param   string  $plugin_file  Path to the plugin file relative to the plugins directory.
	 * @param   array   $plugin_data  An array of plugin data.
	 * @param   string  $status       Status filter currently applied to the plugin list.
	 *
	 * @see   https://developer.wordpress.org/reference/hooks/after_plugin_row_plugin_file/
	 */
	public static function updateMessage($plugin_file = '', $plugin_data = [], $status = '')
	{
		self::thawState();

		// Load enough of our plugin to display translated strings
		try
		{
			$container = \AkeebaBackupWP::loadAkeebaBackupContainer();

			if (!$container)
			{
				return;
			}

			// Load the language files
			$container->language->loadLanguage(null, $container->languagePath . '/akeebabackup');
		}
		catch (Throwable $e)
		{
			return;
		}

		$html     = '';
		$warnings = [];
		$dirSlug  = self::getPluginSlug();

		if (static::$cantUseWpUpdate)
		{
			$updateUrl = 'admin.php?page=' . $dirSlug . '/akeebabackupwp.php&view=update&force=1';

			$warnings[] = sprintf(
				"<p id=\"akeebabackupwp-error-update-noconnection\">%s</p>",
				Text::sprintf('SOLO_UPDATE_WORDPRESS_OLDER_THAN_43', $updateUrl)
			);
		}
		elseif (static::$needsDownloadID)
		{
			$warnings[] = sprintf(
				"<p id=\"akeebabackupwp-error-update-nodownloadid\">%s</p>",
				Text::_('SOLO_UPDATE_ERROR_NEEDSAUTH')
			);
		}
		elseif (static::$connectionError)
		{
			$updateUrl = 'admin.php?page=' . $dirSlug . '/akeebabackupwp.php&view=update&force=1';

			$warnings[] = sprintf(
				"<p id=\"akeebabackupwp-error-update-noconnection\">%s</p>",
				Text::sprintf('SOLO_UPDATE_WORDPRESS_CONNECTION_ERROR', $updateUrl)
			);
		}
		elseif (static::$platformError)
		{
			$warnings[] = sprintf(
				"<p id=\"akeebabackupwp-error-update-platform-mismatch\">%s</p>",
				Text::_('SOLO_UPDATE_WORDPRESS_PLATFORM_HEAD')
			);
		}
		elseif (static::$stabilityError)
		{
			/**
			 * There is an update available, but it's less stable than the minimum stability preference.
			 *
			 * For example: a Beta is available, but we are asked to only report stable versions.
			 *
			 * We deliberately don't show a warning. The whole point of the stability preference is to stop buggering
			 * the poor user during our pre-release runs (alphas, betas and occasional RC). In this case we just pretend
			 * there is no update available, just like we do in the interface of our plugin.
			 */
		}

		if ($warnings)
		{
			$warnings = implode('', $warnings);
			$msg      = Text::_('SOLO_UPDATE_WORDPRESS_WARNING');

			$html = <<<HTML
<tr class="">
	<th></th>
	<td></td>
	<td>
		<div style="border: 1px solid #F0AD4E;border-radius: 3px;background: #fdf5e9;padding:10px">
			<strong>$msg</strong><br/>
			$warnings		
		</div>
	</td>
</tr>
HTML;
		}

		if ($html)
		{
			echo $html;
		}
	}


	/**
	 * Fetches the update information from the remote server
	 *
	 * @return Registry|bool
	 * @throws AWFAppException
	 * @throws UpdateError
	 */
	private static function getUpdateInfo()
	{
		static $updates;

		// If I already have some update info, simply return them
		if ($updates)
		{
			return $updates;
		}

		try
		{
			$container = \AkeebaBackupWP::loadAkeebaBackupContainer();
		}
		catch (Exception $e)
		{
			return false;
		}

		if (!$container)
		{
			return false;
		}

		try
		{
			/** @var \Solo\Model\Update $updateModel */
			$updateModel = $container->mvcFactory->makeModel('Update');
		}
		catch (Exception $e)
		{
			return false;
		}

		$updateModel->load(true);

		// No updates? Let's stop here
		$hasUpdate  = $updateModel->hasUpdate();
		$updateInfo = $updateModel->getUpdateInformation();

		if (!$hasUpdate)
		{
			// Did we get a connection error?
			if ($updateInfo->get('loadedUpdate') == false)
			{
				throw new ConnectionError();
			}

			// We might have an update that does not match the stability preference, e.g. RC with min. stability Stable.
			if ($updateInfo->get('minstabilityMatch') == false)
			{
				throw new StabilityError();
			}

			// mhm... maybe we're on a old WordPress version?
			if (!$updateInfo->get('platformMatch', 0))
			{
				throw new PlatformError();
			}
		}

		return $updateInfo;
	}

	/**
	 * Does the user need to entere a new Download ID?
	 *
	 * @return bool
	 */
	private static function needsDownloadID(): bool
	{
		$container = \AkeebaBackupWP::loadAkeebaBackupContainer();

		if (!$container)
		{
			return false;
		}

		// With the core version we're always good to go
		if (!AKEEBABACKUP_PRO)
		{
			return false;
		}

		// Do we need the Download ID (ie Pro version)?
		$dlid = $container->appConfig->get('options.update_dlid');

		if (!preg_match('/^([0-9]{1,}:)?[0-9a-f]{32}$/i', $dlid))
		{
			return true;
		}

		return false;
	}

	/**
	 * Freeze the update warnings state.
	 *
	 * We create an array with the update warnings flags and save it as a site transient.
	 */
	private static function freezeState(): void
	{
		$frozenState = [];

		foreach (self::STATE_KEYS as $key)
		{
			if (isset(self::${$key}))
			{
				$frozenState[$key] = self::${$key};
			}
		}

		set_site_transient('akeebabackupwp_pluginupdate_frozenstate', $frozenState);
	}

	/**
	 * Unfreeze the update warnings state
	 *
	 * We read the site transient and restore the update warnings flags from it, if it's set.
	 */
	private static function thawState(): void
	{
		$frozenState = get_site_transient('akeebabackupwp_pluginupdate_frozenstate');

		if (empty($frozenState) || !is_array($frozenState))
		{
			return;
		}

		foreach (self::STATE_KEYS as $key)
		{
			if (isset(self::${$key}) && isset($frozenState[$key]))
			{
				self::${$key} = $frozenState[$key];
			}
		}
	}

	/**
	 * Returns the subdirectory of the main WP_CONTENT_DIR/plugins folder where our plugin is installed.
	 *
	 * @return string
	 */
	private static function getPluginSlug(): string
	{
		$pluginsUrl   = plugins_url('', realpath(__DIR__ . '/../akeebabackupwp.php'))
			?: realpath(__DIR__ . '/..');
		$baseUrlParts = explode('/', $pluginsUrl);
		$dirSlug      = end($baseUrlParts);

		if (!empty($dirSlug) && ($dirSlug != '..'))
		{
			return $dirSlug;
		}

		$fullDir  = __DIR__;
		$dirParts = explode(DIRECTORY_SEPARATOR, $fullDir, 3);
		$dirSlug  = $dirParts[1] ?? 'akeebabackupwp';

		return $dirSlug;
	}
}
