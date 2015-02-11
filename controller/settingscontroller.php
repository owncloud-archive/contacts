<?php
/**
 * @author Thomas Tanghus
 * @copyright 2013-2014 Thomas Tanghus (thomas@tanghus.net)
 * @copyright 2015 Lukas Reschke lukas@owncloud.com
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Contacts\Controller;

use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\IConfig;
use OCP\IRequest;

/**
 * Controller class for groups/categories
 */
class SettingsController extends Controller {
	/** @var IConfig */
	private $config;
	/** @var string */
	private $userId;

	/**
	 * @param string $AppName
	 * @param IRequest $request
	 * @param IConfig $config
	 * @param string $UserId
	 */
	public function __construct($AppName,
								IRequest $request,
								IConfig $config,
								$UserId){
		parent::__construct($AppName, $request);
		$this->config = $config;
		$this->userId = $UserId;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $key
	 * @param string $value
	 * @return JSONResponse
	 */
	public function set($key = '', $value = '') {
		$response = new JSONResponse();

		if($key === '' || $value === '') {
			$response->setStatus(Http::STATUS_PRECONDITION_FAILED);
			return $response;
		}

		try {
			$this->config->setUserValue($this->userId, $this->appName, $key, $value);
			$response->setData([
				'key' => $key,
				'value' => $value,
			]);
			return $response;
		} catch (\Exception $e) {
			$response->setStatus(Http::STATUS_INTERNAL_SERVER_ERROR);
			return $response;
		}
	}
}
