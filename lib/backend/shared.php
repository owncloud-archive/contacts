<?php
/**
 * ownCloud - Backend for Shared contacts
 *
 * @author Thomas Tanghus
 * @copyright 2013 Thomas Tanghus (thomas@tanghus.net)
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
 * Shared address books backend
 */

class Shared extends Database {

	public $name = 'shared';
	public $addressBooks = array();

	protected $itemType;
	protected $shareManager;

	public function __construct(
		$userid = null,
		$addressBooksTableName = '*PREFIX*contacts_addressbooks',
		$cardsTableName = '*PREFIX*contacts_cards',
		$indexTableName = '*PREFIX*contacts_cards_properties',
		ShareManager $shareManager
	) {
		parent::__construct($userid, $addressBooksTableName, $cardsTableName, $indexTableName);
		$this->itemType = 'addressbooks';
		$this->shareManager = $shareManager;
	}

	/**
	 * Returns the list of address books for a specific user.
	 *
	 * @param string $principaluri
	 * @return array
	 */
	public function getAddressBooksForUser($userid = null) {
		$userid = $userid ? $userid : $this->userid;
		$filter = array(
			'shareWith' => $userid,
		);
		$shares = $this->shareManger->getShares($this->itemType, $filter);
		foreach ($shares as $share) {
			$addressBookId = $share->getItemSource();
			if (!isset($this->addressBooks[$addressBookId])) {
				$this->addressBooks[$addressBookId] = $share->toAddressBook();
			} else {
				// Combine permissions for duplicate shared address books
				$this->addressBooks[$addressBookId]['permissions'] |= $share->getPermissions();
			}
		}
		return $this->addressBooks;
	}

	/**
	 * Returns a specific address book.
	 *
	 * @param string $addressBookId
	 * @return mixed
	 */
	public function getAddressBook($addressBookId) {
		if (isset($this->addressbooks[$addressBookId])) {
			return $this->addressbooks[$addressBookId];
		} else {
			$filter = array(
				'shareWith' => $this->userid,
				'itemSource' => $addressBookId,
			);
			$shares = $this->shareManger->getShares($this->itemType, $filter);
			if (!empty($shares)) {
				$share = reset($shares);
				$this->addressBooks[$addressBookId] = $share->toAddressBook();
				foreach ($shares as $share) {
					// Combine permissions for duplicate shared address books
					$this->addressBooks[$addressBookId]['permissions'] |= $share->getPermissions();
				}
				return $this->addressBooks[$addressBookId];
			}
		}
		return null;
	}

	/**
	 * Returns all contacts for a specific address book id.
	 *
	 * @param string $addressBookId
	 * @param bool $omitdata Don't fetch the entire carddata or vcard.
	 * @return array
	 */
	public function getContacts($addressBookId, $limit = null, $offset = null, $omitdata = false) {
		$addressBook = $this->getAddressBook($addressBookId);
		if (!$addressBook) {
			throw new \Exception('Shared Address Book not found: ' . $addressBookId, 404);
		}
		$permissions = $addressBook['permissions'];

		$cards = parent::getContacts($addressBookId, $limit, $offset, $omitdata);

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
	 * @param string $addressBookId
	 * @param mixed $id Contact ID
	 * @return array|false
	 */
	public function getContact($addressBookId, $id, $noCollection = false) {
		$addressBook = $this->getAddressBook($addressBookId);
		if (!$addressBook) {
			throw new \Exception('Shared Address Book not found: ' . $addressBookId, 404);
		}
		$permissions = $addressBook['permissions'];

		$card = parent::getContact($addressBookId, $id, $noCollection);
		$card['permissions'] = $permissions;
		return $card;
	}

}
