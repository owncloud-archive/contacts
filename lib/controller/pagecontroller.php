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

use OCA\Contacts\App,
	OCA\Contacts\Controller,
	OCA\Contacts\Utils\Properties,
	OCP\AppFramework\Http\TemplateResponse;


/**
 * Controller class for groups/categories
 */
class PageController extends Controller {

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index() {
		\OC::$server->getNavigationManager()->setActiveEntry('contacts');

		$imppTypes = Properties::getTypesForProperty('IMPP');
		$adrTypes = Properties::getTypesForProperty('ADR');
		$phoneTypes = Properties::getTypesForProperty('TEL');
		$emailTypes = Properties::getTypesForProperty('EMAIL');
		$ims = Properties::getIMOptions();
		$imProtocols = array();
		foreach($ims as $name => $values) {
			$imProtocols[$name] = $values['displayname'];
		}

		$maxUploadFilesize = \OCP\Util::maxUploadFilesize('/');

		\OCP\Util::addScript('', 'jquery.multiselect');
		\OCP\Util::addScript('', 'tags');
		\OCP\Util::addScript('contacts', 'jquery.combobox');
		\OCP\Util::addScript('contacts', 'modernizr.custom');
		\OCP\Util::addScript('contacts', 'app');
		\OCP\Util::addScript('contacts', 'addressbooks');
		\OCP\Util::addScript('contacts', 'contacts');
		\OCP\Util::addScript('contacts', 'storage');
		\OCP\Util::addScript('contacts', 'groups');
		\OCP\Util::addScript('contacts', 'jquery.ocaddnew');
		\OCP\Util::addScript('contacts', 'dialog');
		\OCP\Util::addScript('files', 'jquery.fileupload');
		\OCP\Util::addScript('3rdparty/Jcrop', 'jquery.Jcrop');
		\OCP\Util::addStyle('', 'jquery.multiselect');
		\OCP\Util::addStyle('contacts', 'jquery.combobox');
		\OCP\Util::addStyle('contacts', 'jquery.ocaddnew');
		\OCP\Util::addStyle('3rdparty/Jcrop', 'jquery.Jcrop');
		\OCP\Util::addStyle('contacts', 'contacts');

		$response = new TemplateResponse('contacts', 'contacts');
		$response->setParams(array(
			'uploadMaxFilesize' => $maxUploadFilesize,
			'uploadMaxHumanFilesize' => \OCP\Util::humanFileSize($maxUploadFilesize),
			'phoneTypes' => $phoneTypes,
			'emailTypes' => $emailTypes,
			'adrTypes' => $adrTypes,
			'imppTypes' => $imppTypes,
			'imProtocols' => $imProtocols,
		));

		return $response;
	}
}
