<?php

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
    
    public function getAddressBooksForUser(array $options = array()) {
        // Only 1 addressbook for every user
        $sql = 'SELECT * FROM ' . $this->addressBooksTableName . ' WHERE id = ?';
        $args = array($this->userid);
        $query = \OCP\DB::prepare($sql);
        $result = $query->execute($args);
        $row = $result->fetchRow();
        // Check if there are no results TODO?
        if(!$row){
            // Create new addressbook
            $sql = 'INSERT INTO ' . $this->addressBooksTableName 
                    . ' ( '
                        . 'id, '
                        . 'displayname, '
                        //. 'uri, ' TODO
                        . 'description, '
                //        . 'ctag, '
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
            $query->execute($args);
            
            return $this->getAddressBooksForUser();
        } else {
            $row['permissions'] = \OCP\PERMISSION_ALL;
            return array($row);
        }
    }
        
    public function getAddressBook($addressBookId, array $options = array()) {
        
        $sql = 'SELECT * FROM ' . $this->addressBooksTableName . ' WHERE id = ?';
        $args = array($addressBookId);
        $query = \OCP\DB::prepare($sql);
        $result = $query->execute($args);
        $row = $result->fetchRow();
        $row['permissions'] = \OCP\PERMISSION_ALL;
        $row['backend'] = $this->name;

        return array($row);
    }
    
    public function getContacts($addressbookid, array $options = array()){
        $contacts =  array();
        
        $sql = 'SELECT * FROM ' . $this->cardsTableName . ' WHERE owner = ?';
        $query = \OCP\DB::prepare($sql);
        $result = $query->execute(array($this->userid));
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
    
    public function getContact($addressbookid, $id, array $options = array()){
        $sql = 'SELECT * FROM ' . $this->cardsTableName . ' WHERE owner = ?';
        $query = \OCP\DB::prepare($sql);
        $result = $query->execute(array($this->userid));
        $row = $result->fetchRow();
        $row['permissions'] = \OCP\PERMISSION_ALL;
        
        return $row;
    }
    
    public function createAddressBook(array $properties) {
        
    }
    
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
        }
    }
    
    private function removeContacts($contacts, $addressbookid){
        foreach($contacts as $user){
            $sql = 'DELETE FROM ' . $this->cardsTableName . ' WHERE owner = ? AND id = ?';
            
            $query = \OCP\DB::prepare($sql);
            $result = $query->execute(array($this->userid, $user));
        }
    }
    
    
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
