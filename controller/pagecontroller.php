<?php
/**
 * @author Thomas Tanghus
 * @copyright 2013-2014 Thomas Tanghus (thomas@tanghus.net)
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Contacts\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCA\Contacts\Utils\Properties;
use OCA\Contacts\ImportManager;
use OCA\Contacts\Factory\UtilFactory;

/**
 * Controller class for groups/categories
 */
class PageController extends Controller {
	/** @var ImportManager */
	private $importManager;
	/** @var UtilFactory */
	private $utilFactory;

	/**
	 * @param string $AppName
	 * @param IRequest $request
	 * @param ImportManager $importManager
	 * @param UtilFactory $utilFactory
	 */
	public function __construct($AppName,
								IRequest $request,
								ImportManager $importManager,
								UtilFactory $utilFactory){
		parent::__construct($AppName, $request);
		$this->importManager = $importManager;
		$this->utilFactory = $utilFactory;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index() {
		$imppTypes = Properties::getTypesForProperty('IMPP');
		$adrTypes = Properties::getTypesForProperty('ADR');
		$phoneTypes = Properties::getTypesForProperty('TEL');
		$emailTypes = Properties::getTypesForProperty('EMAIL');
		$cloudTypes = Properties::getTypesForProperty('CLOUD');
		$ims = Properties::getIMOptions();
		$imProtocols = array();
		foreach($ims as $name => $values) {
			$imProtocols[$name] = $values['displayname'];
		}

		$maxUploadFilesize = $this->utilFactory->maxUploadFilesize('/');

		\OCP\Util::addScript('placeholder', null);
		\OCP\Util::addScript('../vendor/blueimp-md5/js/md5', null);
		\OCP\Util::addScript('jquery.avatar', null);
		\OCP\Util::addScript('avatar', null);

		$response = new TemplateResponse($this->appName, 'contacts');
		$response->setParams([
			'uploadMaxFilesize' => $maxUploadFilesize,
			'uploadMaxHumanFilesize' => $this->utilFactory->humanFileSize($maxUploadFilesize),
			'phoneTypes' => $phoneTypes,
			'emailTypes' => $emailTypes,
			'cloudTypes' => $cloudTypes,
			'adrTypes' => $adrTypes,
			'imppTypes' => $imppTypes,
			'imProtocols' => $imProtocols,
			'importManager' => $this->importManager,
		]);

		return $response;
	}
}
