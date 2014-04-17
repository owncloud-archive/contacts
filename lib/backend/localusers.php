<?php
/**
 * ownCloud - ownCloud users backend for Contacts
 *
 * @author Tobia De Koninck
 * @copyright 2014 Tobia De Koninck (tobia@ledfan.be)
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

use OCA\Contacts\Contact,
	OCA\Contacts\VObject\VCard,
	OCA\Contacts\Utils\Properties,
	Sabre\VObject\Reader,
	OCA\Contacts\Addressbook,
	OCA\Contacts\LocalUsersAddressbookProvider;

/**
 * Contact backend for storing all the ownCloud users in this installation.
 * Every user has *1* personal addressbook. The id of this addresbook is the 
 * userid of the owner.
 */
class LocalUsers extends AbstractBackend {

	public $name = 'localusers';

	/**
	* The table that holds the address books.
	* For every user there is *1* addressbook.
	* @var string
	*/
	private $addressBooksTableName = '*PREFIX*contacts_ocu_addressbooks';

	/**
	* The table that holds the contacts.
	* @var string
	*/
	private $cardsTableName = '*PREFIX*contacts_ocu_cards';

	/**
	* The table that holds the properties of the contacts.
	* This is used to provice a search function.
	* @var string
	*/
	private $indexTableName = '*PREFIX*contacts_ocu_cards_properties';

	/**
	* All possible properties which can be stored in the $indexTableName.
	* @var string
	*/
	private $indexProperties = array(
		'BDAY', 'UID', 'N', 'FN', 'TITLE', 'ROLE', 'NOTE', 'NICKNAME',
		'ORG', 'CATEGORIES', 'EMAIL', 'TEL', 'IMPP', 'ADR', 'URL', 'GEO'
	);

	/**
	* language object
	* @var OC_L10N
	*/
	public static $l10n;

	/**
	* Defaults object
	* @var OC_Defaults
	*/
	public static $defaults;

	public function __construct($userid) {
		self::$l10n = \OCP\Util::getL10N('contacts');
		self::$defaults = new \OCP\Defaults();
		$this->userid = $userid ? $userid : \OCP\User::getUser();
	}

	/**
	* {@inheritdoc}
	*/
	public function getAddressBooksForUser(array $options = array()) {
		return array($this->getAddressBook($this->userid));
	}

	/**
	* {@inheritdoc}
	* Only 1 addressbook for every user
	*/
	public function getAddressBook($addressBookId, array $options = array()) {
		$addressbook = array(
			"id" => $addressBookId,
			"displayname" => (string)self::$l10n->t('On this %s', array(self::$defaults->getName())),
			"description" => (string)self::$l10n->t('On this %s', array(self::$defaults->getName())),
			"lastmodified" => time(),
			/* FIXME: we need on 'owner' here */
			"permissions" => \OCP\PERMISSION_READ,
			"backend" => $this->name,
			"active" => 1
		);
		return $addressbook;
	}

	/**
	* {@inheritdoc}
	* There are as many contacts in this addressbook as in this ownCloud installation
	*/
	public function getContacts($addressbookid, array $options = array()) {
		$this->updateDatabase();
		$contacts =  array();
		try{
			$sql = 'SELECT * FROM ' . $this->cardsTableName . ' WHERE addressbookid = ?';
			$query = \OCP\DB::prepare($sql);
			$result = $query->execute(array($this->userid));

			if (\OCP\DB::isError($result)) {
				\OCP\Util::writeLog('contacts', __METHOD__. 'DB error: '
					. \OC_DB::getErrorMessage($result), \OCP\Util::ERROR);
				return $contacts;
			} else {
				while($row = $result->fetchRow()){
					$row['permissions'] = \OCP\PERMISSION_READ | \OCP\PERMISSION_UPDATE;
					$contacts[] = $row;
				}
			}
			return $contacts;
		} catch(\Exception $e) {
			\OCP\Util::writeLog('contacts', __METHOD__.' exception: '
				. $e->getMessage(), \OCP\Util::ERROR);
			return $contacts;
		}
	
	}

	/**
	* {@inheritdoc}
	* If your username is "admin" and you want to retrieve your own contact
	* the params would be: $addressbookid = 'admin'; $id = 'admin';
	* If your username is 'foo' and you want to retrieve the contact with
	* ownCloud username 'bar' the params would be: $addressbookid = 'foo'; $id = 'bar';
	*/
	public function getContact($addressbookid, $id, array $options = array()) {
		try{
			$sql = 'SELECT * FROM ' . $this->cardsTableName . ' WHERE addressbookid = ? AND id = ?';
			$query = \OCP\DB::prepare($sql);
			$result = $query->execute(array($this->userid, $id));

			if (\OCP\DB::isError($result)) {
				\OCP\Util::writeLog('contacts', __METHOD__. 'DB error: '
					. \OC_DB::getErrorMessage($result), \OCP\Util::ERROR);
				return null;
			} else {
				$row = $result->fetchRow();
				$row['permissions'] = \OCP\PERMISSION_READ | \OCP\PERMISSION_UPDATE;
				return $row;
			}
		} catch(\Exception $e) {
			\OCP\Util::writeLog('contacts', __METHOD__.' exception: '
			. $e->getMessage(), \OCP\Util::ERROR);
			return null;
		}
	}

	/**
	* Help function to add contacts to an addressbook.
	* This only happens when an admin creates new users
	* @param array $contacts array with userid of ownCloud users
	* @param string $addressBookId
	* @return bool
	*/
	private function addContacts($contacts, $addressbookid) {
		foreach($contacts as $user){
			try {
				$sql = 'INSERT INTO ' . $this->cardsTableName . ' ('
					. 'id, '
					. 'addressbookid, '
					. 'fullname, ' /* Change to displayname*/
					. 'carddata, '
					. 'lastmodified'
				. ') VALUES ('
					. '?,'
					. '?,'
					. '?,'
					. '?,'
					. '?'
				. ')';

				$query = \OCP\DB::prepare($sql);

				$vcard = \Sabre\VObject\Component::create('VCARD');
				$vcard->FN = \OCP\User::getDisplayName($user);
				$now = new \DateTime('now');
				$vcard->REV = $now->format(\DateTime::W3C);

				$appinfo = \OCP\App::getAppInfo('contacts');
				$appversion = \OCP\App::getAppVersion('contacts');
				$prodid = '-//ownCloud//NONSGML ' . $appinfo['name'] . ' ' . $appversion.'//EN';
				$vcard->PRODID = $prodid;
				$vcard->add('IMPP', 'x-owncloud-handle:' . $user, array("X-SERVICE-TYPE" => array("owncloud-handle")));

				$result = $query->execute(array($user, $this->userid, \OCP\User::getDisplayName($user), $vcard->serialize(), time()));

				if (\OCP\DB::isError($result)) {
					\OCP\Util::writeLog('contacts', __METHOD__. 'DB error: '
					. \OC_DB::getErrorMessage($result), \OCP\Util::ERROR);
					return false;
				} else {
					// All done
					// now update the index table with all the properties
					$this->updateIndex($user, $vcard);
					return true;
				}
			} catch(\Exception $e) {
				\OCP\Util::writeLog('contacts', __METHOD__.' exception: '
					. $e->getMessage(), \OCP\Util::ERROR);
				return false;
			}
		}
	}

	/**
	* Help function to remove contacts from an addressbook.
	* This only happens when an admin remove an ownCloud user
	* @param array $contacts array with userid of ownCloud users
	* @param string $addressBookId
	* @return bool
	*/
	private function removeContacts($contacts, $addressbookid) {
		foreach($contacts as $user){
			try {
				$sql = 'DELETE FROM ' . $this->cardsTableName . ' WHERE addressbookid = ? AND id = ?';
				$query = \OCP\DB::prepare($sql);
				$result = $query->execute(array($this->userid, $user));
				if (\OCP\DB::isError($result)) {
					\OCP\Util::writeLog('contacts', __METHOD__. 'DB error: '
					. \OC_DB::getErrorMessage($result), \OCP\Util::ERROR);
					return false;
				} else {
					return true;
				}
			} catch(\Exception $e) {
				\OCP\Util::writeLog('contacts', __METHOD__.' exception: '
					. $e->getMessage(), \OCP\Util::ERROR);
				return false;
			}
		}
	}

	/**
	* @inheritdoc
	*/
	public function updateContact($addressBookId, $id, $contact, array $options = array()) {

		$updateRevision = true;
		$isCardDAV = false;

		if (!$contact instanceof VCard) {
			try {
				$contact = Reader::read($contact);
			} catch(\Exception $e) {
				\OCP\Util::writeLog('contacts', __METHOD__.', exception: '.$e->getMessage(), \OCP\Util::ERROR);
				return false;
			}
		}

		if ($updateRevision || !isset($contact->REV)) {
			$now = new \DateTime;
			$contact->REV = $now->format(\DateTime::W3C);
		}

		try{
			$sql = 'UPDATE ' . $this->cardsTableName
			. ' SET '
				. '`fullname` = ?, '
				. '`carddata` = ?, '
				. '`lastmodified` = ? '
			. ' WHERE '
				. '`id` = ? '
				. 'AND `addressbookid` = ? ';
			$query = \OCP\DB::prepare($sql);
			$result = $query->execute(array($contact->FN, $contact->serialize(), time(), $id, $this->userid));
			if (\OCP\DB::isError($result)) {
				\OCP\Util::writeLog('contacts', __METHOD__. 'DB error: '
					. \OC_DB::getErrorMessage($result), \OCP\Util::ERROR);
				return false;
			} else {
				// All done
				// now update the indexes in the DB
				$this->updateIndex($id, $contact);
				return true;
			}
		} catch(\Exception $e) {
			\OCP\Util::writeLog('contacts', __METHOD__.' exception: '
			. $e->getMessage(), \OCP\Util::ERROR);
			return false;
		}
	}

	/**
	* This is a hack so backends can have different search functions.
	* @return \OCA\Contacts\LocalUsersAddressbookProvider
	*/
	public function getSearchProvider($addressBook) {
		return new LocalUsersAddressbookProvider($addressBook);
	}

	/**
	* Updates the index table. All properties of a contact are stored in it.
	* Needed for the search function.
	* @param type $contactId
	* @param type $vcard
	* @return boolean
	*/
	private function updateIndex($contactId, $vcard) {
		// Utils\Properties::updateIndex($parameters['id'], $contact);
		$this->purgeIndex($contactId);
		$updatestmt = \OCP\DB::prepare('INSERT INTO `' . $this->indexTableName . '` '
					. '(`addressbookid`, `contactid`,`name`,`value`,`preferred`) VALUES(?,?,?,?,?)');
		// Insert all properties in the table
		foreach($vcard->children as $property) {
			if(!in_array($property->name, $this->indexProperties)) {
				continue;
			}
			$preferred = 0;
			foreach($property->parameters as $parameter) {
				if($parameter->name == 'TYPE' && strtoupper($parameter->value) == 'PREF') {
					$preferred = 1;
					break;
				}
			}
			try {
				$result = $updatestmt->execute(
					array(
					\OCP\User::getUser(),
					$contactId,
					$property->name,
					substr($property->value, 0, 254),
					$preferred,
					)
				);
				if (\OCP\DB::isError($result)) {
					\OCP\Util::writeLog('contacts', __METHOD__. 'DB error: '
					. \OC_DB::getErrorMessage($result), \OCP\Util::ERROR);
					return false;
				}
			} catch(\Exception $e) {
				\OCP\Util::writeLog('contacts', __METHOD__.', exception: '.$e->getMessage(), \OCP\Util::ERROR);
				return false;
			}
		}
	}

	/**
	* Remove all indexes from the table.
	* This is always called before adding new properties.
	* @param type $contactId
	* @param type $vcard
	* @return boolean
	*/
	private function purgeIndex($id) {
		// Remove all indexes from the table
		try {
			$query = \OCP\DB::prepare('DELETE FROM `' . $this->indexTableName . '`'
			. ' WHERE `contactid` = ?');
			$query->execute(array($id));

		} catch(\Exception $e) {
			return false;
		}
	}

	public function updateDatabase() {
		$sql = 'SELECT * FROM ' . $this->cardsTableName . ' WHERE addressbookid = ?';
		$query = \OCP\DB::prepare($sql);
		$result = $query->execute(array($this->userid));

		if (\OCP\DB::isError($result)) {
			\OCP\Util::writeLog('contacts', __METHOD__. 'DB error: '
			. \OC_DB::getErrorMessage($result), \OCP\Util::ERROR);
			return true;
		} else {
			$contactsId = array();
			while($row = $result->fetchRow()) {
				$contactsId[] = $row['id'];
			}

			$users = \OCP\User::getUsers();

			$add = array_diff($users, $contactsId);
			$remove = array_diff($contactsId, $users);
			if(count($add) > 0) {
				$this->addContacts($add, $addressbookid);
				$recall = true;
			}

			if(count($remove) > 0) {
				$this->removeContacts($remove, $addressbookid);
				$recall = true;
			}
			return true;
		}
	}

	/**
	 * Don't cache
	 */
	public function lastModifiedAddressBook($addressBookId) {
		return time();
	}

}
