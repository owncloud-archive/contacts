<?php
/**
* ownCloud
*
* @author Michael Gapczynski
* @copyright 2012 Michael Gapczynski mtgap@owncloud.com
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
*/

namespace OCA\Contacts\Share;
use OCA\Contacts\App;

class Contact implements \OCP\Share_Backend {

	const FORMAT_CONTACT = 0;

	/**
	 * @var \OCA\Contacts\App;
	 */
	public $app;

	/**
	 * @var \OCA\Contacts\Backend\Database;
	 */
	public $backend;

	public function __construct() {
		$this->app = new App(\OCP\User::getUser());
		$this->backend = $this->app->getBackend('local');
	}

	public function isValidSource($itemSource, $uidOwner) {
		// TODO: Cache address books.
		$app = new App($uidOwner);
		$userAddressBooks = $app->getAddressBooksForUser();

		foreach ($userAddressBooks as $addressBook) {
			if ($addressBook->childExists($itemSource)) {
				return true;
			}
		}
		return false;
	}

	public function generateTarget($itemSource, $shareWith, $exclude = null) {
		// TODO Get default addressbook and check for conflicts
		$contact = $this->backend->getContact(null, $itemSource,
			array('noCollection' => true));
		return $contact['fullname'];
	}

	public function formatItems($items, $format, $parameters = null) {
		$contacts = array();
		if ($format == self::FORMAT_CONTACT) {
			foreach ($items as $item) {
				$contacts[] = $this->backend->getContact(null, $item,
					array('noCollection' => true));
			}
		}
		return $contacts;
	}

	public function isShareTypeAllowed($shareType) {
		return true;
	}

}
