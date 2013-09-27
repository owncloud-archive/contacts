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
	OCA\Contacts\ImageResponse,
	OCA\Contacts\Utils\JSONSerializer,
	OCA\Contacts\Utils\Properties,
	OCA\Contacts\Controller,
	OCP\AppFramework\Http\Http;

/**
 * Controller class For Contacts
 */
class ContactController extends Controller {

	/**
	 * @NoAdminRequired
	 */
	public function getContact() {

		$request = $this->request;
		$response = new JSONResponse();

		$addressBook = $this->app->getAddressBook($params['backend'], $params['addressbookid']);
		$contact = $addressBook->getChild($params['contactid']);

		if(!$contact) {
			$response->bailOut(App::$l10n->t('Couldn\'t find contact.'));
			return $response;
		}

		$data = JSONSerializer::serializeContact($contact);

		$response->setParams($data);

		return $response;
	}

	/**
	 * @NoAdminRequired
	 */
	public function saveContact() {

		$request = $this->request;
		$params = $this->request->urlParams;
		$response = new JSONResponse();

		$addressBook = $this->app->getAddressBook($params['backend'], $params['addressbookid']);
		$contact = $addressBook->getChild($params['contactid']);

		if(!$contact) {
			$response->bailOut(App::$l10n->t('Couldn\'t find contact.'));
			return $response;
		}

		if(!$contact->mergeFromArray($request->params)) {
			$response->bailOut(App::$l10n->t('Error merging into contact.'));
			return $response;
		}
		if(!$contact->save()) {
			$response->bailOut(App::$l10n->t('Error saving contact to backend.'));
			return $response;
		}
		$data = JSONSerializer::serializeContact($contact);

		$response->setParams($data);

		return $response;
	}

	/**
	 * @NoAdminRequired
	 */
	public function deleteProperty() {

		$request = $this->request;
		$params = $request->urlParams;
		$response = new JSONResponse();

		$name = $request->post['name'];
		$checksum = isset($request->post['checksum']) ? $request->post['checksum'] : null;

		$response->debug(__METHOD__ . ', name: ' . print_r($name, true));
		$response->debug(__METHOD__ . ', checksum: ' . print_r($checksum, true));

		$addressBook = $this->app->getAddressBook($params['backend'], $params['addressbookid']);
		$contact = $addressBook->getChild($params['contactid']);

		if(!$contact) {
			$response->bailOut(App::$l10n->t('Couldn\'t find contact.'));
			return $response;
		}
		if(!$name) {
			$response->bailOut(App::$l10n->t('Property name is not set.'));
			return $response;
		}
		if(!$checksum && in_array($name, Properties::$multi_properties)) {
			$response->bailOut(App::$l10n->t('Property checksum is not set.'));
			return $response;
		}
		if(!is_null($checksum)) {
			try {
				$contact->unsetPropertyByChecksum($checksum);
			} catch(Exception $e) {
				$response->bailOut(App::$l10n->t('Information about vCard is incorrect. Please reload the page.'));
				return $response;
			}
		} else {
			unset($contact->{$name});
		}
		if(!$contact->save()) {
			$response->bailOut(App::$l10n->t('Error saving contact to backend.'));
			return $response;
		}

		$response->setParams(array(
			'backend' => $request->parameters['backend'],
			'addressbookid' => $request->parameters['addressbookid'],
			'contactid' => $request->parameters['contactid'],
			'lastmodified' => $contact->lastModified(),
		));

		return $response;
	}

	/**
	 * @NoAdminRequired
	 */
	public function saveProperty() {
		$params = $this->request->urlParams;
		$request = json_decode(file_get_contents('php://input'), true);

		//$request = $requestData;
		$response = new JSONResponse();
		$response->debug(__METHOD__ .', upload_max_filesize: ' . ini_get('upload_max_filesize'));

		$name = $request['name'];
		$value = $request['value'];
		$checksum = isset($request['checksum']) ? $request['checksum'] : null;
		$parameters = isset($request['parameters']) ? $request['parameters'] : null;
		$response->debug(__METHOD__ . ', name: ' . print_r($name, true));
		$response->debug(__METHOD__ . ', value: ' . print_r($value, true));
		$response->debug(__METHOD__ . ', checksum: ' . print_r($checksum, true));
		$response->debug(__METHOD__ . ', parameters: ' . print_r($parameters, true));

		$addressBook = $this->app->getAddressBook($params['backend'], $params['addressbookid']);
		//$response->debug(__METHOD__ . ', addressBook: ' . print_r($addressBook, true));
		$contact = $addressBook->getChild($params['contactid']);

		if(!$contact) {
			return $response->bailOut(App::$l10n->t('Couldn\'t find contact.'));
		}
		if(!$name) {
			return $response->bailOut(App::$l10n->t('Property name is not set.'));
		}
		if(!$checksum && in_array($name, Properties::$multi_properties)) {
			return $response->bailOut(App::$l10n->t('Property checksum is not set.'));
		}
		if(is_array($value)) {
			// NOTE: Important, otherwise the compound value will be
			// set in the order the fields appear in the form!
			ksort($value);
		}
		$result = array('contactid' => $params['contactid']);
		if(!$checksum && in_array($name, Properties::$multi_properties)) {
			return $response->bailOut(App::$l10n->t('Property checksum is not set.'));
		} elseif($checksum && in_array($name, Properties::$multi_properties)) {
			try {
				$checksum = $contact->setPropertyByChecksum($checksum, $name, $value, $parameters);
				$result['checksum'] = $checksum;
			} catch(Exception $e)	{
				return $response->bailOut(App::$l10n->t('Information about vCard is incorrect. Please reload the page.'));
			}
		} elseif(!in_array($name, Properties::$multi_properties)) {
			if(!$contact->setPropertyByName($name, $value, $parameters)) {
				return $response->bailOut(App::$l10n->t('Error setting property'));
			}
		}
		if(!$contact->save()) {
			return $response->bailOut(App::$l10n->t('Error saving property to backend'));
		}
		$result['lastmodified'] = $contact->lastModified();

		return $response->setData($result);

	}

}

