<?php
/**
 * @package   solo
 * @copyright Copyright (c)2014-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Solo\Controller;

use Awf\Mvc\DataController;
use Awf\Text\Text;

/**
 * Common controller superclass. Reserved for future use.
 */
abstract class DataControllerDefault extends DataController
{
	protected $aclChecks = array(
		'alice'          => array('*' => array('configure')),
		'backup'         => array('*' => array('backup')),
		'browser'        => array('*' => array('configure')),
		'configuration'  => array('*' => array('configure')),
		'dbfilters'      => array('*' => array('configure')),
		'discover'       => array('*' => array('configure')),
		'extradirs'      => array('*' => array('configure')),
		'fsfilters'      => array('*' => array('configure')),
		'log'            => array('*' => array('configure')),
		'manage'         => array(
			'manage'      => array(),
			'showComment' => array('backup'),
			'cancel'      => array('backup'),
			'download'    => array('download'),
			'restore'     => array('configure'),
			'*'           => array('download'),
		),
		'multidb'        => array('*' => array('configure')),
		'profiles'       => array('*' => array('configure')),
		'profile'        => array('*' => array('configure')),
		'regexdbfilters' => array('*' => array('configure')),
		'regexfsfilters' => array('*' => array('configure')),
		'remotefiles'    => array('*' => array('download')),
		'restore'        => array('*' => array('configure')),
		's3import'       => array('*' => array('configure')),
		'schedule'       => array('*' => array('configure')),
		'sysconfig'      => array('*' => array('configure', 'backup', 'download')),
		'transfer'       => array('*' => array('download')),
		'update'         => array('*' => array('configure', 'backup', 'download')),
		'upload'         => array('*' => array('backup')),
		'users'          => array('*' => array('configure', 'backup', 'download')),
		'wizard'         => array('*' => array('configure')),
	);

	public function execute($task)
	{
		$view = $this->input->getCmd('view', 'main');

		$this->aclCheck($view, $task);

		return parent::execute($task);
	}

	protected function aclCheck($view, $task)
	{
		$view = strtolower($view);
		$task = strtolower($task);

		if (!isset($this->aclChecks[$view]))
		{
			return;
		}

		if (!isset($this->aclChecks[$view][$task]))
		{
			if (!isset($this->aclChecks[$view]['*']))
			{
				return;
			}

			$requiredPrivileges = $this->aclChecks[$view]['*'];
		}
		else
		{
			$requiredPrivileges = $this->aclChecks[$view][$task];
		}

		$user = $this->container->userManager->getUser();

		foreach ($requiredPrivileges as $privilege)
		{
			if (!$user->getPrivilege('akeeba.' . $privilege))
			{
				throw new \RuntimeException(Text::_('SOLO_ERR_ACLDENIED'), 403);
			}
		}
	}
}
