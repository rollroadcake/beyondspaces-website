<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Filesystem;
use Awf\Application\Application;
use Awf\Container\Container;
use Awf\Container\ContainerAwareInterface;
use Awf\Container\ContainerAwareTrait;
use Awf\Utils\HashHelper;

final class Factory
{
	/**
	 * Filesystem adapter instances
	 *
	 * @var  array[FilesystemInterface]
	 */
	private static $instances = array();

	private function __construct()
	{
		// Do not allow instantiation of this class
	}

	/**
	 * Get a filesystem abstraction adapter based on the configuration of the provided application object
	 *
	 * @param   Container  $container  The application which provides the configuration
	 * @param   boolean    $hybrid     Should I return a hybrid adapter?
	 *
	 * @return  FilesystemInterface  The filesystem abstraction adapter
	 */
	public static function getAdapter(?Container $container = null, bool $hybrid = false)
	{
		/** @deprecated 2.0 The container argument will become mandatory */
		if (empty($container))
		{
			trigger_error(
				sprintf('The container argument is mandatory in %s', __METHOD__),
				E_USER_DEPRECATED
			);

			$container = Application::getInstance()->getContainer();
		}

		$config = $container->appConfig;

		$defaultPort = ($config->get('fs.driver',	'file') == 'ftp') ? '21' : '22';

		$options = array(
			'driver'		=> $config->get('fs.driver',	'file'),
			'host'			=> $config->get('fs.host',		'localhost'),
			'port'			=> $config->get('fs.port',	$defaultPort),
			'username'		=> $config->get('fs.username',	''),
			'password'		=> $config->get('fs.password',	''),
			'directory'		=> $config->get('fs.dir',		''),
			'ssl'			=> $config->get('fs.ssl',		false),
			'passive'		=> $config->get('fs.passive',	true),
		);

		$classPrefix = '\\Awf\\Filesystem\\';
		$className = $classPrefix . ucfirst($options['driver']);

		if (!class_exists($className))
		{
			$hybrid = false;
			$className = $classPrefix . 'File';
		}
		elseif ($hybrid)
		{
			$className = $classPrefix . 'Hybrid';
		}

		$signature = HashHelper::md5($container->application_name . $className . ($hybrid ? 'hybrid' : ''));

		if (!isset(self::$instances[$signature]))
		{
			self::$instances[$signature] = new $className($options, $container);
		}

		return self::$instances[$signature];
	}
}
