<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License version 3, or later
 *
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, version 3.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program. If not, see
 * <https://www.gnu.org/licenses/>.
 */

namespace Akeeba\Engine\DevPlatform\Command;

use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use Silly\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ConfigList
{
	public static function register(Application $app)
	{
		$app
			->command('config:list [--profile=]', new self())
			->defaults(
				[
					'profile' => 1,
				]
			)
			->descriptions(
				'List profile configuration parameters',
				[
					'--profile' => 'Profile ID',
				]
			);
	}

	public function __invoke(int $profile, InputInterface $input, OutputInterface $output, SymfonyStyle $io)
	{
		define('AKEEBA_PROFILE', $profile);
		$platform = Platform::getInstance();
		$platform->load_configuration($profile);

		$config = Factory::getConfiguration();

		$info = $this->squash(json_decode(Factory::getEngineParamsProvider()->getJsonGuiDefinition(), true));

		$table = $io->createTable();
		$table->setHeaders([
			'Key', 'Name', 'Value'
		]);

		foreach ($info as $key => $details)
		{
			$table->addRow([
				$key,
				$details['title'],
				$config->get($key),
			]);
		}

		$table->render();
	}

	protected function squash(array $derp)
	{
		$ret = [];

		foreach ($derp as $key => $value)
		{
			if (strpos($key, '.') !== false)
			{
				$ret[$key] = $value;

				continue;
			}

			if (!is_array($value))
			{
				continue;
			}

			$more = $this->squash($value);
			$ret = array_merge($ret, $more);
		}

		return $ret;
	}
}