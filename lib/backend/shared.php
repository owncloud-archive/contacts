<?php
/**
 * ownCloud - Backend for Shared contacts
 *
 * @author Thomas Tanghus
 * @copyright 2013-2014 Thomas Tanghus (thomas@tanghus.net)
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

namespace OCA\Contacts\Backend;

use OCA\Contacts;

/**
 * Subclass this class for Cantacts backends
 */

class Shared extends Database {

	public $name = 'shared';
	public $addressbooks = array();

	/**
	 * Returns the list of addressbooks for a specific user.
	 *
	 * @param string $principaluri
	 * @return array
	 */
	public function getAddressBooksForUser(array $options = array()) {

		// workaround for https://github.com/owncloud/core/issues/2814
		$maybeSharedAddressBook = \OCP\Share::getItemsSharedWith(
			'addressbook',
			Contacts\Share\Addressbook::FORMAT_ADDRESSBOOKS
		);

		foreach ($maybeSharedAddressBook as $sharedAddressbook) {

			if (isset($sharedAddressbook['id'])) {
				$this->addressbooks[] = $this->getAddressBook($sharedAddressbook['id']);
			}

		}

		foreach ($this->addressbooks as &$addressBook) {
			$addressBook['backend'] = $this->name;
		}

		return $this->addressbooks;
	}

	/**
	 * Returns a specific address book.
	 *
	 * @param string $addressbookid
	 * @param mixed $id Contact ID
	 * @return mixed
	 */
	public function getAddressBook($addressbookid, array $options = array()) {

		foreach ($this->addressbooks as $addressBook) {

			if ($addressBook['id'] === $addressbookid) {
				return $addressBook;
			}

		}

		$addressBook = \OCP\Share::getItemSharedWithBySource(
			'addressbook',
			$addressbookid,
			Contacts\Share\Addressbook::FORMAT_ADDRESSBOOKS
		);

		// Not sure if I'm doing it wrongly, or if its supposed to return
		// the info in an array?
		$addressBook = (isset($addressBook['permissions']) ? $addressBook : $addressBook[0]);
		$addressBook['backend'] = $this->name;
		return $addressBook;
	}

	/**
	 * Returns all contacts for a specific addressbook id.
	 *
	 * @param string $addressbookid
	 * @param bool $omitdata Don't fetch the entire carddata or vcard.
	 * @return array
	 */
	public function getContacts($addressbookid, array $options = array()) {

		$addressBook = $this->getAddressBook($addressbookid);

		if (!$addressBook) {
			throw new \Exception('Shared Address Book not found: ' . $addressbookid, 404);
		}

		$permissions = $addressBook['permissions'];

		$cards = parent::getContacts($addressbookid, $options);

		foreach ($cards as &$card) {
			$card['permissions'] = $permissions;
		}

		return $cards;
	}

	/**
	 * Returns a specific contact.
	 *
	 * The $id for Database and Shared backends can be an array containing
	 * either 'id' or 'uri' to be able to play seamlessly with the
	 * CardDAV backend.
	 * @see \Database\getContact
	 *
	 * @param string $addressbookid
	 * @param mixed $id Contact ID
	 * @return array|false
	 */
	public function getContact($addressbookid, $id, array $options = array()) {

		$addressBook = $this->getAddressBook($addressbookid);

		if (!$addressBook) {
			throw new \Exception('Shared Address Book not found: ' . $addressbookid, 404);
		}

		$permissions = $addressBook['permissions'];

		$card = parent::getContact($addressbookid, $id, $options);

		if (!$card) {
			throw new \Exception('Shared Contact not found: ' . implode(',', $id), 404);
		}

		$card['permissions'] = $permissions;
		return $card;
	}
}
