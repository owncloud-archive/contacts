<?php
/**
 * @author Thomas Tanghus
 * @copyright 2013-2014 Thomas Tanghus (thomas@tanghus.net)
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Contacts\Controller;

use OCA\Contacts\App,
	OCA\Contacts\JSONResponse,
	OCA\Contacts\ImageResponse,
	OCA\Contacts\Utils\Properties,
	OCA\Contacts\Utils\TemporaryPhoto,
	OCA\Contacts\Controller;

/**
 * Controller class For Contacts
 */
class ContactPhotoController extends Controller {

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function getPhoto($maxSize = 170) {
		// TODO: Cache resized photo
		$params = $this->request->urlParams;
		$etag = null;

		$addressBook = $this->app->getAddressBook($params['backend'], $params['addressBookId']);
		$contact = $addressBook->getChild($params['contactId']);

		if(!$contact) {
			$response = new JSONResponse();
			$response->bailOut(App::$l10n->t('Couldn\'t find contact.'));
			return $response;
		}

		$image = new \OCP\Image();
		if (isset($contact->PHOTO) && $image->loadFromBase64((string)$contact->PHOTO)) {
			// OK
			$etag = md5($contact->PHOTO);
		}
		else
		// Logo :-/
		if(isset($contact->LOGO) && $image->loadFromBase64((string)$contact->LOGO)) {
			// OK
			$etag = md5($contact->LOGO);
		}
		if($image->valid()) {
			$response = new ImageResponse($image);
			$lastModified = $contact->lastModified();
			// Force refresh if modified within the last minute.
			if(!is_null($lastModified)) {
				$response->setLastModified(\DateTime::createFromFormat('U', $lastModified) ?: null);
			}
			if(!is_null($etag)) {
				$response->setETag($etag);
			}
			if ($image->width() > $maxSize || $image->height() > $maxSize) {
				$image->resize($maxSize);
			}
			return $response;
		} else {
			$response = new JSONResponse();
			$response->bailOut(App::$l10n->t('Error getting user photo'));
			return $response;
		}
	}

	/**
	 * Uploads a photo and saves in oC cache
	 * @return JSONResponse with data.tmp set to the key in the cache.
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function uploadPhoto() {
		$params = $this->request->urlParams;


		$tempPhoto = TemporaryPhoto::get(
			$this->server,
			TemporaryPhoto::PHOTO_UPLOADED,
			$this->request
		);

		$response = new JSONResponse();

		return $response->setParams(array(
			'tmp'=>$tempPhoto->getKey(),
			'metadata' => array(
				'contactId'=> $params['contactId'],
				'addressBookId'=> $params['addressBookId'],
				'backend'=> $params['backend'],
			),
		));
	}

	/**
	 * Saves the photo from the contact being edited to oC cache
	 * @return JSONResponse with data.tmp set to the key in the cache.
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function cacheCurrentPhoto() {
		$params = $this->request->urlParams;
		$response = new JSONResponse();

		$addressBook = $this->app->getAddressBook($params['backend'], $params['addressBookId']);
		$contact = $addressBook->getChild($params['contactId']);

		$tempPhoto = TemporaryPhoto::get(
			$this->server,
			TemporaryPhoto::PHOTO_CURRENT,
			$contact
		);

		return $response->setParams(array(
			'tmp'=>$tempPhoto->getKey(),
			'metadata' => array(
				'contactId'=> $params['contactId'],
				'addressBookId'=> $params['addressBookId'],
				'backend'=> $params['backend'],
			),
		));
	}

	/**
	 * Saves the photo from ownCloud FS to oC cache
	 * @return JSONResponse with data.tmp set to the key in the cache.
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function cacheFileSystemPhoto() {
		$params = $this->request->urlParams;
		$maxSize = isset($this->request->get['maxSize']) ? $this->request->get['maxSize'] : 400;
		$response = new JSONResponse();

		if(!isset($this->request->get['path'])) {
			$response->bailOut(App::$l10n->t('No photo path was submitted.'));
		}

		$tempPhoto = TemporaryPhoto::get(
			$this->server,
			TemporaryPhoto::PHOTO_FILESYSTEM,
			$this->request->get['path']
		);

		return $response->setParams(array(
			'tmp'=>$tempPhoto->getKey(),
			'metadata' => array(
				'contactId'=> $params['contactId'],
				'addressBookId'=> $params['addressBookId'],
				'backend'=> $params['backend'],
			),
		));
	}

	/**
	 * Get a photo from the oC cache for cropping.
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function getTempPhoto() {
		$params = $this->request->urlParams;
		$tmpkey = $params['key'];

		$image = new \OCP\Image();
		$image->loadFromData($this->server->getCache()->get($tmpkey));
		if($image->valid()) {
			$response = new ImageResponse($image);
			return $response;
		} else {
			$response = new JSONResponse();
			return $response->bailOut('Error getting temporary photo');
		}
	}

	/**
	 * Get a photo from the oC and crops it with the suplied geometry.
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function cropPhoto() {
		$params = $this->request->urlParams;
		$x = (isset($this->request->post['x']) && $this->request->post['x']) ? $this->request->post['x'] : 0;
		$y = (isset($this->request->post['y']) && $this->request->post['y']) ? $this->request->post['y'] : 0;
		$w = (isset($this->request->post['w']) && $this->request->post['w']) ? $this->request->post['w'] : -1;
		$h = (isset($this->request->post['h']) && $this->request->post['h']) ? $this->request->post['h'] : -1;
		$tmpkey = $params['key'];

		$app = new App($this->api->getUserId());
		$addressBook = $app->getAddressBook($params['backend'], $params['addressBookId']);
		$contact = $addressBook->getChild($params['contactId']);

		$response = new JSONResponse();

		if(!$contact) {
			return $response->bailOut(App::$l10n->t('Couldn\'t find contact.'));
		}

		$data = $this->server->getCache()->get($tmpkey);
		if(!$data) {
			return $response->bailOut(App::$l10n->t('Image has been removed from cache'));
		}

		$image = new \OCP\Image();

		if(!$image->loadFromData($data)) {
			return $response->bailOut(App::$l10n->t('Error creating temporary image'));
		}

		$w = ($w !== -1 ? $w : $image->width());
		$h = ($h !== -1 ? $h : $image->height());

		if(!$image->crop($x, $y, $w, $h)) {
			return $response->bailOut(App::$l10n->t('Error cropping image'));
		}

		// For vCard 3.0 the type must be e.g. JPEG or PNG
		// For version 4.0 the full mimetype should be used.
		// https://tools.ietf.org/html/rfc2426#section-3.1.4
		if(strval($contact->VERSION) === '4.0') {
			$type = $image->mimeType();
		} else {
			$type = explode('/', $image->mimeType());
			$type = strtoupper(array_pop($type));
		}
		if(isset($contact->PHOTO)) {
			$property = $contact->PHOTO;
			if(!$property) {
				$this->server->getCache()->remove($tmpkey);
				return $response->bailOut(App::$l10n
					->t('Error getting PHOTO property.'));
			}
			$property->setValue(strval($image));
			$property->parameters = array();
			$property->parameters[]
				= new \Sabre\VObject\Parameter('ENCODING', 'b');
			$property->parameters[]
				= new \Sabre\VObject\Parameter('TYPE', $image->mimeType());
			$contact->PHOTO = $property;
		} else {
			$contact->add('PHOTO',
				strval($image), array('ENCODING' => 'b',
				'TYPE' => $type));
			// TODO: Fix this hack
			$contact->setSaved(false);
		}
		if(!$contact->save()) {
			return $response->bailOut(App::$l10n->t('Error saving contact.'));
		}

		$thumbnail = Properties::cacheThumbnail(
			$params['backend'],
			$params['addressBookId'],
			$params['contactId'],
			$image
		);

		$response->setData(array(
			'status' => 'success',
			'data' => array(
				'id' => $params['contactId'],
				'thumbnail' => $thumbnail,
			)
		));

		$this->server->getCache()->remove($tmpkey);

		return $response;
	}

}