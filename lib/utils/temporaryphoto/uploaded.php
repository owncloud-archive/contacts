<?php
/**
 * ownCloud - Load an uploaded image from system.
 *
 * @author Thomas Tanghus
 * @copyright 2014 Thomas Tanghus (thomas@tanghus.net)
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Contacts\Utils\TemporaryPhoto;

use OCA\Contacts\Contact as ContactObject,
	OCA\Contacts\Utils\TemporaryPhoto as AbstractTemporaryPhoto;

/**
 * This class loads an image from the virtual file system.
 */
class Uploaded extends AbstractTemporaryPhoto {

	/**
	 * The request to read the data from
	 *
	 * @var \OCP\IRequest
	 */
	protected $input;

	public function __construct(\OCP\IServerContainer $server, \OCP\IRequest $request) {
		\OCP\Util::writeLog('contacts', __METHOD__, \OCP\Util::DEBUG);
		if (!$request instanceOf \OCP\IRequest) {
			throw new \Exception(
				__METHOD__ . ' Second argument must be an instance of \\OCP\\IRequest'
			);
		}

		parent::__construct($server);
		$this->request = $request;
		$this->processImage();
	}

	/**
	 * Load the image.
	 */
	protected function processImage() {
		$this->image = new \OCP\Image();
		\OCP\Util::writeLog('contacts', __METHOD__ . ', Content-Type: ' . $this->request->getHeader('Content-Type'), \OCP\Util::DEBUG);
		\OCP\Util::writeLog('contacts', __METHOD__ . ', Content-Length: ' . $this->request->getHeader('Content-Length'), \OCP\Util::DEBUG);

		$this->image->loadFromFileHandle($this->request->put);
	}

}