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

		$addressBook = $this->app->getAddressBook($params['backend'], $params['addressBookId']);
		$contact = $addressBook->getChild($params['contactId']);

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

		$addressBook = $this->app->getAddressBook($params['backend'], $params['addressBookId']);
		$contact = $addressBook->getChild($params['contactId']);

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
	public function patch() {
		$params = $this->request->urlParams;

		$patch = $this->request->patch;
		$response = new JSONResponse();
		$response->debug(__METHOD__ .', upload_max_filesize: ' . ini_get('upload_max_filesize'));

		$name = $patch['name'];
		$value = $patch['value'];
		$checksum = isset($patch['checksum']) ? $patch['checksum'] : null;
		$parameters = isset($patch['parameters']) ? $patch['parameters'] : null;
		$response->debug(__METHOD__ . ', name: ' . print_r($name, true));
		$response->debug(__METHOD__ . ', value: ' . print_r($value, true));
		$response->debug(__METHOD__ . ', checksum: ' . print_r($checksum, true));
		$response->debug(__METHOD__ . ', parameters: ' . print_r($parameters, true));

		$addressBook = $this->app->getAddressBook($params['backend'], $params['addressBookId']);
		//$response->debug(__METHOD__ . ', addressBook: ' . print_r($addressBook, true));
		$contact = $addressBook->getChild($params['contactId']);

		if(!$contact) {
			return $response
				->setStatus(Http::STATUS_NOT_FOUND)
				->bailOut(App::$l10n->t('Couldn\'t find contact.'));
		}
		if(!$name) {
			return $response
				->setStatus(Http::STATUS_PRECONDITION_FAILED)
				->bailOut(App::$l10n->t('Property name is not set.'));
		}
		if(!$checksum && in_array($name, Properties::$multi_properties)) {
			return $response
				->setStatus(Http::STATUS_PRECONDITION_FAILED)
				->bailOut(App::$l10n->t('Property checksum is not set.'));
		}
		if(is_array($value)) {
			// NOTE: Important, otherwise the compound value will be
			// set in the order the fields appear in the form!
			ksort($value);
		}
		$result = array('contactId' => $params['contactId']);
		if($checksum && in_array($name, Properties::$multi_properties)) {
			try {
				if(is_null($value)) {
					$contact->unsetPropertyByChecksum($checksum);
				} else {
					$checksum = $contact->setPropertyByChecksum($checksum, $name, $value, $parameters);
					$result['checksum'] = $checksum;
				}
			} catch(Exception $e)	{
				return $response
					->setStatus(Http::STATUS_PRECONDITION_FAILED)
					->bailOut(App::$l10n->t('Information about vCard is incorrect. Please reload the page.'));
			}
		} elseif(!in_array($name, Properties::$multi_properties)) {
			if(is_null($value)) {
				unset($contact->{$name});
			} else {
				if(!$contact->setPropertyByName($name, $value, $parameters)) {
					return $response
						->setStatus(Http::STATUS_INTERNAL_SERVER_ERROR)
						->bailOut(App::$l10n->t('Error updating contact'));
				}
			}
		}
		if(!$contact->save()) {
			return $response->bailOut(App::$l10n->t('Error saving contact to backend'));
		}
		$result['lastmodified'] = $contact->lastModified();

		return $response->setData($result);

	}

}

