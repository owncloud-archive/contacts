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
	return array($this->getAddressBook('admin'));
    }

    /**
     * {@inheritdoc}
     * Only 1 addressbook for every user
     */
    public function getAddressBook($addressBookId, array $options = array()) {
	$addressbook = array(
	    "id" => $addressBookId,
	    "displayname" => 'ownCloudUsers',
	    "description" => 'ownCloud Users',
	    "ctag" => time(),
	    "permissions" => \OCP\PERMISSION_READ,
	    "backend" => $this->name,
	    "active" => 1
	);
	//var_dump($addressbook);
	//throw new \Exception($addressBookId);
	
	return $addressbook;
    }

     /**
     * {@inheritdoc}
     * There are as many contacts in this addressbook as in this ownCloud installation
     */
    public function getContacts($addressbookid, array $options = array()){
	$contacts =  array();
	try{ 
	    $sql = 'SELECT * FROM ' . $this->cardsTableName . ' WHERE addressbookid = ?';
	    $query = \OCP\DB::prepare($sql);
	    $result = $query->execute(array($this->userid));
	    
	    if (\OCP\DB::isError($result)) {
		\OCP\Util::writeLog('contacts', __METHOD__. 'DB error: '
		    . \OC_DB::getErrorMessage($result), \OCP\Util::ERROR);
		return array();
	    } else {
		while($row = $result->fetchRow()){
		    $row['permissions'] = \OCP\PERMISSION_UPDATE;
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
	    return array();
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
	    $sql = 'SELECT * FROM ' . $this->cardsTableName . ' WHERE addressbookid = ?';
	    $query = \OCP\DB::prepare($sql);
	    $result = $query->execute(array($this->userid));
		    
	    if (\OCP\DB::isError($result)) {
		\OCP\Util::writeLog('contacts', __METHOD__. 'DB error: '
		    . \OC_DB::getErrorMessage($result), \OCP\Util::ERROR);
		return array();
	    } else {
		$row = $result->fetchRow();
		$row['permissions'] = \OCP\PERMISSION_UPDATE;
		return $row;
	    }
	} catch(\Exception $e) {
	    \OCP\Util::writeLog('contacts', __METHOD__.' exception: '
		. $e->getMessage(), \OCP\Util::ERROR);
	    return array();
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
	    try{ 
		$sql = 'INSERT INTO ' . $this->cardsTableName . ' ('
		    . 'id, '
		    . 'addressbookid, '
		    . 'fullname, '
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
		
		
		$result = $query->execute(array($user, $this->userid, \OCP\User::getDisplayName($user), $vcard->serialize(), time()));

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
     * Help function to remove contacts from an addressbook. 
     * This only happens when an admin remove an ownCloud user
     * @param array $contacts array with userid of ownCloud users 
     * @param string $addressBookId
     * @return bool
     */
    private function removeContacts($contacts, $addressbookid){
	foreach($contacts as $user){
	      try{ 
		$sql = 'DELETE FROM ' . $this->cardsTableName . ' WHERE owner = ? AND id = ?';
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

	$data = $contact->serialize();
	
	try{ 
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
