<?php

namespace OCA\Contacts;

class LocalUsersAddressbookProvider implements \OCP\IAddressBook {

    public function __construct(){
	
    }
    
    /**
    * @param $pattern
    * @param $searchProperties
    * @param $options
    * @return array|false
    */
    public function search($pattern, $searchProperties, $options) {
	if(in_array("FN", $searchProperties) && in_array("id", $searchProperties)){
	    $query = 'SELECT DISTINCT * FROM `*PREFIX*contacts_ocu_cards` WHERE addressbookid = ? AND (`id` LIKE ? OR `fullname` LIKE ?) ';
	    $stmt = \OCP\DB::prepare($query);
	    $result = $stmt->execute(array(\OCP\User::getUser(), '%' . $pattern . "%", '%' . $pattern . "%"));
	} elseif(in_array("FN", $searchProperties)){
	    $query = 'SELECT * FROM `*PREFIX*contacts_ocu_cards` WHERE addressbookid = ? AND `fullname` LIKE ? ';
	    $stmt = \OCP\DB::prepare($query);
	    $result = $stmt->execute(array(\OCP\User::getUser(), '%' . $pattern . "%"));
	} elseif(in_array("id", $searchProperties)){
	    $query = 'SELECT * FROM `*PREFIX*contacts_ocu_cards` WHERE addressbookid = ? AND `id` LIKE ? ';
	    $stmt = \OCP\DB::prepare($query);
	    $result = $stmt->execute(array(\OCP\User::getUser(), '%' . $pattern . "%"));
	} else {
	    $query = 'SELECT * FROM `*PREFIX*contacts_ocu_cards` WHERE addressbookid = ?';
	    $stmt = \OCP\DB::prepare($query);
	    $result = $stmt->execute(array(\OCP\User::getUser()));
	}
	
	if (\OCP\DB::isError($result)) {
	    \OCP\Util::writeLog('contacts', __METHOD__ . 'DB error: ' . \OC_DB::getErrorMessage($result),
		    \OCP\Util::ERROR);
	    return false;
	}
	
	$contacts = array();
	
	while( $row = $result->fetchRow()) {
	    $contacts[] = $row;
	}

	return $contacts;
    }
    
    public function getKey(){
	
    }

    /**
    * In comparison to getKey() this function returns a human readable (maybe translated) name
    * @return mixed
    */
    public function getDisplayName(){

}

    public function createOrUpdate($properties){
	
    }
    
    /**
    * @return mixed
    */
    public function getPermissions(){
	
    }

    /**
    * @param object $id the unique identifier to a contact
    * @return bool successful or not
    */
    public function delete($id){
	 
    }

}
