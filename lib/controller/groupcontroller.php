<?php
/**
 * @author Thomas Tanghus
 * Copyright (c) 2013 Thomas Tanghus (thomas@tanghus.net)
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Contacts\Controller;

use OCA\Contacts\App;
use OCA\Contacts\JSONResponse;
use OCA\AppFramework\Controller\Controller as BaseController;
use OCA\AppFramework\Core\API;


/**
 * Controller class for groups/categories
 */
class GroupController extends BaseController {

	/**
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @Ajax
	 */
	public function getGroups() {
		$app = new App($this->api->getUserId());
		$catmgr = new \OC_VCategories('contact', $this->api->getUserId());
		$categories = $catmgr->categories(\OC_VCategories::FORMAT_MAP);
		foreach($categories as &$category) {
			$ids = $catmgr->idsForCategory($category['name']);
			$category['contacts'] = $ids;
		}

		$favorites = $catmgr->getFavorites();

		$groups = array(
			'categories' => $categories,
			'favorites' => $favorites,
			'shared' => \OCP\Share::getItemsSharedWith('addressbook', \OCA\Contacts\Share\Addressbook::FORMAT_ADDRESSBOOKS),
			'lastgroup' => \OCP\Config::getUserValue($this->api->getUserId(), 'contacts', 'lastgroup', 'all'),
			'sortorder' => \OCP\Config::getUserValue($this->api->getUserId(), 'contacts', 'groupsort', ''),
			);

		return new JSONResponse($groups);
	}

	/**
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @Ajax
	 */
	public function addGroup() {
		$name = $this->request->post['name'];

		$response = new JSONResponse();
		if(is_null($name) || $name === "") {
			$response->bailOut(App::$l10n->t('No group name given.'));
		}

		$catman = new \OC_VCategories('contact', $this->api->getUserId());
		$id = $catman->add($name);

		if($id === false) {
			$response->bailOut(App::$l10n->t('Error adding group.'));
		} else {
			$response->setParams(array('id'=>$id, 'name' => $name));
		}
		return $response;
	}

	/**
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @Ajax
	 */
	public function deleteGroup() {
		$name = $this->request->post['name'];

		$response = new JSONResponse();
		if(is_null($name) || $name === '') {
			$response->bailOut(App::$l10n->t('No group name given.'));
			return $response;
		}

		$catman = new \OC_VCategories('contact', $this->api->getUserId());
		try {
			$ids = $catman->idsForCategory($name);
		} catch(\Exception $e) {
			$response->setErrorMessage($e->getMessage());
			return $response;
		}
		if($ids !== false) {
			$app = new App($this->api->getUserId());
			$backend = $app->getBackend('local');
			foreach($ids as $id) {
				$contact = $backend->getContact(null, $id, array('noCollection' => true));
				$obj = \Sabre\VObject\Reader::read(
					$contact['carddata'],
					\Sabre\VObject\Reader::OPTION_IGNORE_INVALID_LINES
				);
				if($obj) {
					if(!isset($obj->CATEGORIES)) {
						continue;
					}
					if($obj->CATEGORIES->removeGroup($name)) {
						// TODO: don't let updateContact trigger emits, but do it here instead.
						$backend->updateContact(null, $id, $obj, array('noCollection' => true, 'isBatch' => true));
					}
				} else {
					\OCP\Util::writeLog('contacts', __METHOD__.', could not parse card ' . $id, \OCP\Util::DEBUG);
				}
			}
		}
		try {
			$catman->delete($name);
		} catch(\Exception $e) {
			$response->setErrorMessage($e->getMessage());
		}
		return $response;
	}

	/**
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @Ajax
	 */
	public function renameGroup() {
		$from = $this->request->post['from'];
		$to = $this->request->post['to'];

		$response = new JSONResponse();
		if(is_null($from) || $from === '') {
			$response->bailOut(App::$l10n->t('No group name to rename from given.'));
			return $response;
		}
		if(is_null($to) || $to === '') {
			$response->bailOut(App::$l10n->t('No group name to rename to given.'));
			return $response;
		}

		$catman = new \OC_VCategories('contact', $this->api->getUserId());
		if(!$catman->rename($from, $to)) {
			$response->bailOut(App::$l10n->t('Error renaming group.'));
			return $response;
		}
		$ids = $catman->idsForCategory($to);
		if($ids !== false) {
			$app = new App($this->api->getUserId());
			$backend = $app->getBackend('local');
			foreach($ids as $id) {
				$contact = $backend->getContact(null, $id, array('noCollection' => true));
				$obj = \Sabre\VObject\Reader::read(
					$contact['carddata'],
					\Sabre\VObject\Reader::OPTION_IGNORE_INVALID_LINES
				);
				if($obj) {
					if(!isset($obj->CATEGORIES)) {
						continue;
					}
					$obj->CATEGORIES->renameGroup($from, $to);
					$backend->updateContact(null, $id, $obj, array('noCollection' => true));
				} else {
					\OCP\Util::writeLog('contacts', __METHOD__.', could not parse card ' . $id, \OCP\Util::DEBUG);
				}
			}
		}
		return $response;
	}

	/**
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @Ajax
	 */
	public function addToGroup() {
		$response = new JSONResponse();
		$params = $this->request->urlParams;
		$categoryid = $params['categoryid'];
		$categoryname = $this->request->post['name'];
		$ids = $this->request->post['contactids'];
		$response->debug('request: '.print_r($this->request->post, true));

		if(is_null($categoryid) || $categoryid === '') {
			$response->bailOut(App::$l10n->t('Group ID missing from request.'));
			return $response;
		}

		if(is_null($categoryid) || $categoryid === '') {
			$response->bailOut(App::$l10n->t('Group name missing from request.'));
			return $response;
		}

		if(is_null($ids)) {
			$response->bailOut(App::$l10n->t('Contact ID missing from request.'));
			return $response;
		}

		$app = new App($this->api->getUserId());
		$backend = $app->getBackend('local');
		$catman = new \OC_VCategories('contact', $this->api->getUserId());
		foreach($ids as $contactid) {
			$contact = $backend->getContact(null, $contactid, array('noCollection' => true));
			$obj = \Sabre\VObject\Reader::read(
				$contact['carddata'],
				\Sabre\VObject\Reader::OPTION_IGNORE_INVALID_LINES
			);
			if($obj) {
				if(!isset($obj->CATEGORIES)) {
					$obj->add('CATEGORIES');
				}
				$obj->CATEGORIES->addGroup($categoryname);
				$backend->updateContact(null, $contactid, $obj, array('noCollection' => true));
			}
			$response->debug('contactid: ' . $contactid . ', categoryid: ' . $categoryid);
			$catman->addToCategory($contactid, $categoryid);
		}

		return $response;
	}

	/**
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @Ajax
	 */
	public function removeFromGroup() {
		$response = new JSONResponse();
		$params = $this->request->urlParams;
		$categoryid = $params['categoryid'];
		$categoryname = $this->request->post['name'];
		$ids = $this->request->post['contactids'];
		//$response->debug('request: '.print_r($this->request->post, true));

		if(is_null($categoryid) || $categoryid === '') {
			$response->bailOut(App::$l10n->t('Group ID missing from request.'));
			return $response;
		}

		if(is_null($ids)) {
			$response->bailOut(App::$l10n->t('Contact ID missing from request.'));
			return $response;
		}

		$app = new App($this->api->getUserId());
		$backend = $app->getBackend('local');
		$catman = new \OC_VCategories('contact', $this->api->getUserId());
		foreach($ids as $contactid) {
			$contact = $backend->getContact(null, $contactid, array('noCollection' => true));
			if(!$contact) {
				$response->debug('Couldn\'t get contact: ' . $contactid);
				continue;
			}
			$obj = \Sabre\VObject\Reader::read(
				$contact['carddata'],
				\Sabre\VObject\Reader::OPTION_IGNORE_INVALID_LINES
			);
			if($obj) {
				if(!isset($obj->CATEGORIES)) {
					$obj->add('CATEGORIES');
				}
				$obj->CATEGORIES->removeGroup($categoryname);
				$backend->updateContact(null, $contactid, $obj, array('noCollection' => true));
			} else {
				$response->debug('Error parsing contact: ' . $contactid);
			}
			$response->debug('contactid: ' . $contactid . ', categoryid: ' . $categoryid);
			$catman->removeFromCategory($contactid, $categoryid);
		}

		return $response;
	}

}

