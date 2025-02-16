<?php
/**
 * @package   solo
 * @copyright Copyright (c)2014-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Solo\Model\OAuth2;

use Solo\Model\OAuth2\AbstractProvider;

class BoxEngine extends AbstractProvider
{
	protected string $tokenEndpoint = 'https://api.box.com/oauth2/token';

	protected string $engineNameForHumans = 'Box.com';

	public function getAuthenticationUrl(): string
	{
		$this->checkConfiguration();

		[$id, $secret] = $this->getIdAndSecret();

		$params = [
			'response_type' => 'code',
			'client_id'     => $id,
			'redirect_uri'  => $this->getUri('step2'),
		];

		return 'https://account.box.com/api/oauth2/authorize?' . http_build_query($params);
	}
}