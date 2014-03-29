<?php

namespace OCA\Contacts\Backend;

use OCA\Contacts\Contact,
	OCA\Contacts\VObject\VCard,
	OCA\Contacts\Utils\Properties,
	Sabre\VObject\Reader;

class OwnCloudUsers extends AbstractBackend {

    public $name = 'OwnCloudUsers';
    
    private $addressBooksTableName = '*PREFIX*contacts_ocu_addressbooks';
    private $cardsTableName = '*PREFIX*contacts_ocu_cards';
    
    public function __construct($userid){
        $this->userid = $userid ? $userid : \OCP\User::getUser();
    }
    
    public function getAddressBooksForUser(array $options = array()) {
        // Only 1 addressbook for every user
        $sql = 'SELECT * FROM ' . $this->addressBooksTableName . ' WHERE userid = ?';
        $args = array($this->userid);
        $query = \OCP\DB::prepare($sql);
        $result = $query->execute($args);
        $row = $result->fetchRow();
        $row['permissions'] = \OCP\PERMISSION_ALL;
        
        return array($row);
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
        
        $sql = 'SELECT * FROM ' . $this->cardsTableName . ' WHERE id = ?';
        $args = array($addressBookId);
        $query = \OCP\DB::prepare($sql);
        $result = $query->execute($args);
        $row = $result->fetchRow();
        $row['permissions'] = \OCP\PERMISSION_ALL;
        $row['backend'] = $this->name;
    }
    
    public function getContact($addressbookid, $id, array $options = array()){
        
    }
    
    public function createAddressBook(array $properties) {
        
    }
    
    private function syncDbWithOc(){
        
    }
    
    
}
