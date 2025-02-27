<?php
/**
 * @package   solo
 * @copyright Copyright (c)2014-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Solo\View\ViewTraits;

trait ShowOnTrait
{
	/**
	 * Converts ShowOn expressions to the internal data required by showon.js
	 *
	 * @param   string|null  $showOn     The ShowOn expression e.g. `foo:1[AND]bar!:2[OR]baz:bat`
	 * @param   string|null  $arrayName  If the fields are wrapped in an array element, what's the array name
	 *
	 * @return  array
	 * @since   8.2.2
	 */
	private function parseShowOnConditions(?string $showOn, ?string $arrayName = null): array
	{
		if (empty($showOn))
		{
			return [];
		}

		$showOnData  = [];
		$showOnParts = preg_split('#(\[AND\]|\[OR\])#', $showOn, -1, PREG_SPLIT_DELIM_CAPTURE);
		$op          = '';

		foreach ($showOnParts as $showOnPart)
		{
			if (in_array($showOnPart, ['[AND]', '[OR]']))
			{
				$op = trim($showOnPart, '[]');

				continue;
			}

			$compareEqual     = strpos($showOnPart, '!:') === false;
			$showOnPartBlocks = explode(($compareEqual ? ':' : '!:'), $showOnPart, 2);

			$field = $arrayName
				? sprintf("%s[%s]", $arrayName, $showOnPartBlocks[0])
				: $showOnPartBlocks[0];

			$showOnData[] = [
				'field'  => $field,
				'values' => explode(',', $showOnPartBlocks[1]),
				'sign'   => $compareEqual === true ? '=' : '!=',
				'op'     => $op,
			];

			$op = '';
		}

		return $showOnData;
	}

	/**
	 * Generate data-showon attributes from ShowOn conditions
	 *
	 * @param   string|null  $showOn     The ShowOn expression e.g. `foo:1[AND]bar!:2[OR]baz:bat`
	 * @param   string|null  $arrayName  If the fields are wrapped in an array element, what's the array name
	 *
	 * @return  string
	 * @since   8.2.2
	 */
	protected function showOn(?string $showOn, ?string $arrayName = null): string
	{
		$conditions = $this->parseShowOnConditions($showOn, $arrayName);

		return empty($conditions)
			? ''
			: sprintf(
				'data-showon="%s"',
				$this->escape(json_encode($this->parseShowOnConditions($showOn, $arrayName)))
			);
	}
}