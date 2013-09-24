<?php
/**
 * @author Thomas Tanghus
 * Copyright (c) 2013 Thomas Tanghus (thomas@tanghus.net)
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Contacts\Controller;

use OCA\Contacts\App,
	OCA\Contacts\JSONResponse,
	OCA\Contacts\Utils\JSONSerializer,
	OCA\Contacts\Controller;

/**
 * Controller class For Address Books
 */
class AddressBookController extends Controller {

	/**
	 * @NoAdminRequired
	 */
	public function userAddressBooks() {
		$addressBooks = $this->app->getAddressBooksForUser();
		$response = array();
		$lastModified = 0;
		foreach($addressBooks as $addressBook) {
			$data = $addressBook->getMetaData();
			$response[] = $data;
			if(!is_null($data['lastmodified'])) {
				$lastModified = max($lastModified, $data['lastmodified']);
			}
		}

		$response = new JSONResponse(array(
				'addressbooks' => $response,
			));

		if($lastModified > 0) {
			$response->setLastModified(\DateTime::createFromFormat('U', $lastModified) ?: null);
			$response->setETag(md5($lastModified));
		}

		return $response;
	}

	/**
	 * @NoAdminRequired
	 */
	public function getAddressBook() {
		\OCP\Util::writeLog('contacts', __METHOD__, \OCP\Util::DEBUG);
		$params = $this->request->urlParams;

		$addressBook = $this->app->getAddressBook($params['backend'], $params['addressbookid']);
		$lastModified = $addressBook->lastModified();
		$response = new JSONResponse();

		if(!is_null($lastModified)) {
			//$response->addHeader('Cache-Control', 'private, must-revalidate');
			$response->setLastModified(\DateTime::createFromFormat('U', $lastModified) ?: null);
			$response->setETag(md5($lastModified));
		}

		$response->debug('method: ' . $this->request->method);
		if($this->request->method === 'GET') {
			$contacts = array();
			foreach($addressBook->getChildren() as $i => $contact) {
				$result = JSONSerializer::serializeContact($contact);
				if($result !== null) {
					$contacts[] = $result;
				}
			}
			$response->setParams(array('contacts' => $contacts));
		}
		return $response;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function addAddressBook() {
		$params = $this->request->urlParams;

		$response = new JSONResponse();

		$backend = $this->app->getBackend($params['backend']);
		if(!$backend->hasAddressBookMethodFor(\OCP\PERMISSION_CREATE)) {
			throw new \Exception('Not implemented');
		}
		try {
			$id = $backend->createAddressBook($this->request->post);
		} catch(Exception $e) {
			$response->bailOut($e->getMessage());
			return $response;
		}
		if($id === false) {
			$response->bailOut(App::$l10n->t('Error creating address book'));
			return $response;
		}

		$response->setStatus('201');
		$response->setParams($backend->getAddressBook($id));
		return $response;
	}

	/**
	 * @NoAdminRequired
	 */
	public function updateAddressBook() {
		$params = $this->request->urlParams;

		$response = new JSONResponse();

		$addressBook = $this->app->getAddressBook($params['backend'], $params['addressbookid']);
		try {
			if(!$addressBook->update($this->request['properties'])) {
				$response->bailOut(App::$l10n->t('Error updating address book'));
				return $response;
			}
		} catch(Exception $e) {
			$response->bailOut($e->getMessage());
			return $response;
		}
		$response->setParams($addressBook->getMetaData());
		return $response;
	}

	/**
	 * @NoAdminRequired
	 */
	public function deleteAddressBook() {
		$params = $this->request->urlParams;

		$response = new JSONResponse();

		$backend = $this->app->getBackend($params['backend']);

		if(!$backend->hasAddressBookMethodFor(\OCP\PERMISSION_DELETE)) {
			throw new \Exception(
				'The "%s" backend does not support deleting address books', array($backend->name)
			);
		}

		$addressBookInfo = $backend->getAddressBook($params['addressbookid']);

		if(!$addressBookInfo['permissions'] & \OCP\PERMISSION_DELETE) {
			$response->bailOut(App::$l10n->t(
				'You do not have permissions to delete the "%s" address book'),
				array($addressBookInfo['displayname']
			));
			return $response;
		}

		if(!$backend->deleteAddressBook($params['addressbookid'])) {
			$response->bailOut(App::$l10n->t('Error deleting address book'));
			return $response;
		}
		return $response;
	}

	/**
	 * @NoAdminRequired
	 */
	public function activateAddressBook() {
		$params = $this->request->urlParams;

		$response = new JSONResponse();

		$addressBook = $this->app->getAddressBook($params['backend'], $params['addressbookid']);

		$addressBook->setActive($this->request->post['state']);

		return $response;
	}

	/**
	 * @NoAdminRequired
	 */
	public function addChild() {
		$params = $this->request->urlParams;

		$response = new JSONResponse();

		$addressBook = $this->app->getAddressBook($params['backend'], $params['addressbookid']);

		try {
			$id = $addressBook->addChild();
		} catch(Exception $e) {
			$response->bailOut($e->getMessage());
			return $response;
		}

		if($id === false) {
			$response->bailOut(App::$l10n->t('Error creating contact.'));
			return $response;
		}

		$contact = $addressBook->getChild($id);
		$response->setStatus('201');
		$response->setETag($contact->getETag());
		$response->addHeader('Location',
			\OCP\Util::linkToRoute(
				'contacts_contact_get',
				array(
					'backend' => $params['backend'],
					'addressbookid' => $params['addressbookid'],
					'contactid' => $id
				)
			)
		);
		$response->setParams(JSONSerializer::serializeContact($contact));
		return $response;
	}

	/**
	 * @NoAdminRequired
	 */
	public function deleteChild() {
		$params = $this->request->urlParams;

		$response = new JSONResponse();

		$addressBook = $this->app->getAddressBook($params['backend'], $params['addressbookid']);

		try {
			$result = $addressBook->deleteChild($params['contactid']);
		} catch(Exception $e) {
			$response->bailOut($e->getMessage());
			return $response;
		}

		if($result === false) {
			$response->bailOut(App::$l10n->t('Error deleting contact.'));
		}
		$response->setStatus('204');
		return $response;
	}

	/**
	 * @NoAdminRequired
	 */
	public function deleteChildren() {
		$params = $this->request->urlParams;

		$response = new JSONResponse();

		$addressBook = $this->app->getAddressBook($params['backend'], $params['addressbookid']);
		$contacts = $this->request->post['contacts'];

		try {
			$result = $addressBook->deleteChildren($contacts);
		} catch(Exception $e) {
			$response->bailOut($e->getMessage());
			return $response;
		}

		$response->setParams(array('result' => $result));
		return $response;
	}

	/**
	 * @NoAdminRequired
	 */
	public function moveChild() {
		$params = $this->request->urlParams;
		$targetInfo = $this->request->post['target'];

		$response = new JSONResponse();

		// TODO: Check if the backend supports move (is 'local' or 'shared') and use that operation instead.
		// If so, set status 204 and don't return the serialized contact.
		$fromAddressBook = $this->app->getAddressBook($params['backend'], $params['addressbookid']);
		$targetAddressBook = $this->app->getAddressBook($targetInfo['backend'], $targetInfo['id']);
		$contact = $fromAddressBook->getChild($params['contactid']);
		if(!$contact) {
			$response->bailOut(App::$l10n->t('Error retrieving contact.'));
			return $response;
		}
		try {
			$contactid = $targetAddressBook->addChild($contact);
		} catch(Exception $e) {
			$response->bailOut($e->getMessage());
			return $response;
		}
		$contact = $targetAddressBook->getChild($contactid);
		if(!$contact) {
			$response->bailOut(App::$l10n->t('Error saving contact.'));
			return $response;
		}
		if(!$fromAddressBook->deleteChild($params['contactid'])) {
			// Don't bail out because we have to return the contact
			$response->debug(App::$l10n->t('Error removing contact from other address book.'));
		}
		$response->setParams(JSONSerializer::serializeContact($contact));
		return $response;
	}

}

