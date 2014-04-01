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
	OCA\Contacts\Addressbook;


/**
 * Contact backend for storing all the ownCloud users in this installation.
 * Every user has *1* personal addressbook. The id of this addresbook is the 
 * userid of the owner.
 */
class OwnCloudUsers extends AbstractBackend {

    public $name = 'OwnCloudUsers';

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

    public function __construct($userid){
	$this->userid = $userid ? $userid : \OCP\User::getUser();
    }

    /**
     * {@inheritdoc}
     */
    public function getAddressBooksForUser(array $options = array()) {
	// Only 1 addressbook for every user
	$sql = 'SELECT * FROM ' . $this->addressBooksTableName . ' WHERE id = ?';
	$args = array($this->userid);
	$query = \OCP\DB::prepare($sql);
	$result = $query->execute($args);
	$row = $result->fetchRow();

	if(!$row){ // TODO -> better way?
	    // Create new addressbook 
	    try{ 
		$sql = 'INSERT INTO ' . $this->addressBooksTableName 
		    . ' ( '
			. 'id, '
			. 'displayname, '
			//. 'uri, ' TODO
			. 'description, '
			//. 'ctag, '
			. 'active '
		    . ') VALUES ( '
			. '?, '
			. '?, '
			. '?, '
			. '? '
		    . ')';
		$args = array(
		    $this->userid,
		    'ownCloud Users',
		    'ownCloud Users',
		    1
		);
		$query = \OCP\DB::prepare($sql);
		$result = $query->execute($args);

		if (\OCP\DB::isError($result)) {
		    \OCP\Util::writeLog('contacts', __METHOD__. 'DB error: '
			. \OC_DB::getErrorMessage($result), \OCP\Util::ERROR);
		    return array();
		}
	    } catch(\Exception $e) {
		    \OCP\Util::writeLog('contacts', __METHOD__.' exception: '
			. $e->getMessage(), \OCP\Util::ERROR);
		return $this->addressBooks;
	    }
	    
	    return $this->getAddressBooksForUser();
	} else {
	    $row['permissions'] = \OCP\PERMISSION_ALL;
	    return array($row);
	}
    }

    /**
     * {@inheritdoc}
     * Only 1 addressbook for every user
     */
    public function getAddressBook($addressBookId, array $options = array()) {
	try{ 
	    $sql = 'SELECT * FROM ' . $this->addressBooksTableName . ' WHERE id = ?';
	    $args = array($addressBookId);
	    $query = \OCP\DB::prepare($sql);
	    $result = $query->execute($args);
	    
	    
	    if (\OCP\DB::isError($result)) {
		\OCP\Util::writeLog('contacts', __METHOD__. 'DB error: '
		    . \OC_DB::getErrorMessage($result), \OCP\Util::ERROR);
		return array();
	    } else {
		$row = $result->fetchRow();
		// TODO create address book if it doesn't exists
		$row['permissions'] = \OCP\PERMISSION_ALL;
		$row['backend'] = $this->name;
	    }
	} catch(\Exception $e) {
		\OCP\Util::writeLog('contacts', __METHOD__.' exception: '
		    . $e->getMessage(), \OCP\Util::ERROR);
	    return $this->addressBooks;
	}
	return array($row);
    }

     /**
     * {@inheritdoc}
     * There are as many contacts in this addressbook as in this ownCloud installation
     */
    public function getContacts($addressbookid, array $options = array()){
	$contacts =  array();
	try{ 
	    $sql = 'SELECT * FROM ' . $this->cardsTableName . ' WHERE owner = ?';
	    $query = \OCP\DB::prepare($sql);
	    $result = $query->execute(array($this->userid));
	    
	    if (\OCP\DB::isError($result)) {
		\OCP\Util::writeLog('contacts', __METHOD__. 'DB error: '
		    . \OC_DB::getErrorMessage($result), \OCP\Util::ERROR);
		return array();
	    } else {
		while($row = $result->fetchRow()){
		    $row['permissions'] = \OCP\PERMISSION_ALL;
		    $contacts[] = $row;
		}	

		$contactsId = array();

		foreach($contacts as $contact){
		    $contactsId[] = $contact['id'];
		}

		$users = \OCP\User::getUsers();
		$recall = false;

		$add = array_diff($users, $contactsId);
		$remove = array_diff($contactsId, $users);
		if(count($add) > 0){
		    $this->addContacts($add, $addressbookid);
		    $recall = true;
		}

		if(count($remove) > 0){
		    $this->removeContacts($remove, $addressbookid);
		    $recall = true;
		}

		if($recall === true){
		    return $this->getContacts($addressbookid);
		} else {
		    return $contacts;
		}
	    }
	} catch(\Exception $e) {
		\OCP\Util::writeLog('contacts', __METHOD__.' exception: '
		    . $e->getMessage(), \OCP\Util::ERROR);
	    return $this->addressBooks;
	}
	
    }

     /**
     * {@inheritdoc}
     * If your username is "admin" and you want to retrieve your own contact
     * the params would be: $addressbookid = 'admin'; $id = 'admin';
     * If your username is 'foo' and you want to retrieve the contact with 
      * ownCloud username 'bar' the params would be: $addressbookid = 'foo'; $id = 'bar';
     */
    public function getContact($addressbookid, $id, array $options = array()){
	try{ 
	    $sql = 'SELECT * FROM ' . $this->cardsTableName . ' WHERE owner = ?';
	    $query = \OCP\DB::prepare($sql);
	    $result = $query->execute(array($this->userid));
		    
	    if (\OCP\DB::isError($result)) {
		\OCP\Util::writeLog('contacts', __METHOD__. 'DB error: '
		    . \OC_DB::getErrorMessage($result), \OCP\Util::ERROR);
		return array();
	    } else {
		$row = $result->fetchRow();
		$row['permissions'] = \OCP\PERMISSION_ALL;
		return $row;
	    }
	} catch(\Exception $e) {
	    \OCP\Util::writeLog('contacts', __METHOD__.' exception: '
		. $e->getMessage(), \OCP\Util::ERROR);
	    return $this->addressBooks;
	}
    }

    // Not needed since there is only one addressbook for every user
    public function createAddressBook(array $properties) {

    }

    /**
     * Help function to add contacts to an addressbook. 
     * This only happens when an admin creates new users
     * @param array $contacts array with userid of ownCloud users 
     * @param string $addressBookId
     * @return bool
     */
    private function addContacts($contacts, $addressbookid){
	foreach($contacts as $user){
	    $sql = 'INSERT INTO ' . $this->cardsTableName . ' ('
		    . 'id, '
		    . 'owner,'
		    . 'addressbookid, '
		    . 'fullname, '
		    . 'carddata, '
		    . 'uri, '
		    . 'lastmodified'
		. ') VALUES ('
		    . '?,'
		    . '?,'
		    . '?,'
		    . '?,'
		    . '?,'
		    . '?,'
		    . '?'
		. ')';

	    $query = \OCP\DB::prepare($sql);

	    $contact = new Contact(	
		$addressBook = new AddressBook($this , $this->getAddressBooksForUser()), // since there is only one addressbook with OC users for each OC user we can use this function
		$this,
		array(
		    "id" => $user,
		    "lastmodified" => time(), 
		    "displayname" => \OCP\User::getDisplayName($user),
		    "fullname" => \OCP\User::getDisplayName($user)
		)
	    );
	    $carddata = $this->generateCardData($contact);
	    $result = $query->execute(array($user, $this->userid, $addressbookid, \OCP\User::getDisplayName($user), $carddata->serialize(), 'test', time()));
	    // TODO Check if $result succeeded
	}
    }

    /**
     * Help function to remove contacts from an addressbook. 
     * This only happens when an admin remove an ownCloud user
     * @param array $contacts array with userid of ownCloud users 
     * @param string $addressBookId
     * @return bool
     */
    private function removeContacts($contacts, $addressbookid){
	foreach($contacts as $user){
	    $sql = 'DELETE FROM ' . $this->cardsTableName . ' WHERE owner = ? AND id = ?';

	    $query = \OCP\DB::prepare($sql);
	    $result = $query->execute(array($this->userid, $user));
	    // TODO Check if $result succeeded
	}
    }

    /**
     * Help function to generate the carddate which than can be stored in the db 
     * @param string|VCard $data 
     * @return Vcard
     */
    private function generateCardData($data){
	if (!$data instanceof VCard) {
	    try {
		$data = Reader::read($data);
	    } catch(\Exception $e) {
	       \OCP\Util::writeLog('contacts', __METHOD__.', exception: '.$e->getMessage(), \OCP\Util::ERROR);
		return false;
	    }      
	}

	try {
	    $data->validate(VCard::REPAIR|VCard::UPGRADE);
	} catch (\Exception $e) {
	    \OCP\Util::writeLog('contacts', __METHOD__ . ' ' .
		'Error validating vcard: ' . $e->getMessage(), \OCP\Util::ERROR);
	    return false;
	}

	$now = new \DateTime;
	$data->REV = $now->format(\DateTime::W3C);

	$appinfo = \OCP\App::getAppInfo('contacts');
	$appversion = \OCP\App::getAppVersion('contacts');
	$prodid = '-//ownCloud//NONSGML ' . $appinfo['name'] . ' ' . $appversion.'//EN';
	$data->PRODID = $prodid;

	return $data;
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

	if (is_array($id)) {
	    if (isset($id['id'])) {
		$id = $id['id'];
	    } elseif (isset($id['uri'])) {
		$updateRevision = false;
		$isCardDAV = true;
		$id = $this->getIdFromUri($id['uri']);

		if (is_null($id)) {
		    \OCP\Util::writeLog('contacts', __METHOD__ . ' Couldn\'t find contact', \OCP\Util::ERROR);
		    return false;
		}

	    } else {
		throw new \Exception(
		    __METHOD__ . ' If second argument is an array, either \'id\' or \'uri\' has to be set.'
		);
	    }
	}

	if ($updateRevision || !isset($contact->REV)) {
	    $now = new \DateTime;
	    $contact->REV = $now->format(\DateTime::W3C);
	}

	$data = $contact->serialize();

	$sql = 'UPDATE ' . $this->cardsTableName
	    . ' SET '
		. '`addressbookid` = ?, '
		. '`fullname` = ?, '
		. '`carddata` = ?, '
		. '`lastmodified` = ? '
	    . ' WHERE '
		. '`id` = ? '
		. 'AND `owner` = ? ';
	$query = \OCP\DB::prepare($sql);
	$result = $query->execute(array($addressBookId, $contact->FN, $data, time(), $id, $this->userid));
	return true;
    }
}
