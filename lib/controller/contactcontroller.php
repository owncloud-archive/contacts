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
	OCA\AppFramework\Controller\Controller as BaseController,
	OCA\AppFramework\Core\API,
	OCA\AppFramework\Http\TextDownloadResponse;


/**
 * Controller class For Contacts
 */
class ContactController extends BaseController {

	/**
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @Ajax
	 */
	public function getContact() {
		$app = new App($this->api->getUserId());

		$request = $this->request;
		$response = new JSONResponse();

		$addressBook = $app->getAddressBook($params['backend'], $params['addressbookid']);
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
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @CSRFExemption
	 */
	public function exportContact() {
		$app = new App($this->api->getUserId());

		$params = $this->request->urlParams;

		$addressBook = $app->getAddressBook($params['backend'], $params['addressbookid']);
		$contact = $addressBook->getChild($params['contactid']);

		if(!$contact) {
			$response = new JSONResponse();
			$response->bailOut(App::$l10n->t('Couldn\'t find contact.'));
			return $response;
		}

		$name = str_replace(' ', '_', $contact->getDisplayName()) . '.vcf';
		return new TextDownloadResponse($contact->serialize(), $name, 'text/vcard');
	}

	/**
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @Ajax
	 */
	public function saveContact() {
		$app = new App($this->api->getUserId());

		$request = $this->request;
		$params = $this->request->urlParams;
		$response = new JSONResponse();

		$addressBook = $app->getAddressBook($params['backend'], $params['addressbookid']);
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
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @Ajax
	 */
	public function deleteProperty() {
		$app = new App($this->api->getUserId());

		$request = $this->request;
		$params = $request->urlParams;
		$response = new JSONResponse();

		$name = $request->post['name'];
		$checksum = isset($request->post['checksum']) ? $request->post['checksum'] : null;

		$response->debug(__METHOD__ . ', name: ' . print_r($name, true));
		$response->debug(__METHOD__ . ', checksum: ' . print_r($checksum, true));

		$app = new App($this->api->getUserId());
		$addressBook = $app->getAddressBook($params['backend'], $params['addressbookid']);
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
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @Ajax
	 */
	public function saveProperty() {
		$params = $this->request->urlParams;
		$app = new App($this->api->getUserId());

		$request = $this->request;
		$response = new JSONResponse();

		$name = $request->post['name'];
		$value = $request->post['value'];
		$checksum = isset($request->post['checksum']) ? $request->post['checksum'] : null;
		$parameters = isset($request->post['parameters']) ? $request->post['parameters'] : null;

		$response->debug(__METHOD__ . ', name: ' . print_r($name, true));
		$response->debug(__METHOD__ . ', value: ' . print_r($value, true));
		$response->debug(__METHOD__ . ', checksum: ' . print_r($checksum, true));
		$response->debug(__METHOD__ . ', parameters: ' . print_r($parameters, true));

		$addressBook = $app->getAddressBook($params['backend'], $params['addressbookid']);
		$response->debug(__METHOD__ . ', addressBook: ' . print_r($addressBook, true));
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
		if(is_array($value)) {
			// NOTE: Important, otherwise the compound value will be
			// set in the order the fields appear in the form!
			ksort($value);
		}
		$result = array('contactid' => $params['contactid']);
		if(!$checksum && in_array($name, Properties::$multi_properties)) {
			$response->bailOut(App::$l10n->t('Property checksum is not set.'));
			return $response;
		} elseif($checksum && in_array($name, Properties::$multi_properties)) {
			try {
				$checksum = $contact->setPropertyByChecksum($checksum, $name, $value, $parameters);
				$result['checksum'] = $checksum;
			} catch(Exception $e)	{
				$response->bailOut(App::$l10n->t('Information about vCard is incorrect. Please reload the page.'));
				return $response;
			}
		} elseif(!in_array($name, Properties::$multi_properties)) {
			if(!$contact->setPropertyByName($name, $value, $parameters)) {
				$response->bailOut(App::$l10n->t('Error setting property'));
			}
		}
		if(!$contact->save()) {
			$response->bailOut(App::$l10n->t('Error saving property to backend'));
			return $response;
		}
		$result['lastmodified'] = $contact->lastModified();

		$response->setParams($result);

		return $response;
	}

}

