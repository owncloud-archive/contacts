<?php

namespace OCA\Contacts;

use OCA\Contacts\Utils\Properties;


class LocalUsersAddressbookProvider implements \OCP\IAddressBook {

    private $indexTableName = '*PREFIX*contacts_ocu_cards_properties';
    private $contactTableName = '*PREFIX*contacts_ocu_cards';

    
    public function __construct(){
	
    }
    
    /**
    * @param $pattern
    * @param $searchProperties
    * @param $options
    * @return array|false
    */
    public function search($pattern, $searchProperties, $options) {
	$ids = array();
	$results = array();
	$query = 'SELECT DISTINCT `contactid` FROM `' . $this->indexTableName . '` WHERE (';
	$params = array();
	foreach($searchProperties as $property) {
		$params[] = $property;
		$params[] = '%' . $pattern . '%';
		$query .= '(`name` = ? AND `value` LIKE ?) OR ';
	}
	$query = substr($query, 0, strlen($query) - 4);
	$query .= ')';

	$stmt = \OCP\DB::prepare($query);
	$result = $stmt->execute($params);
	if (\OCP\DB::isError($result)) {
		\OCP\Util::writeLog('contacts', __METHOD__ . 'DB error: ' . \OC_DB::getErrorMessage($result),
			\OCP\Util::ERROR);
		return false;
	}
	while( $row = $result->fetchRow()) {
		$ids[] = $row['contactid'];
	}

	if(count($ids) > 0) {
		$query = 'SELECT `' . $this->contactTableName . '`.`addressbookid`, `' . $this->indexTableName . '`.`contactid`, `' 
			. $this->indexTableName . '`.`name`, `' . $this->indexTableName . '`.`value` FROM `' 
			. $this->indexTableName . '`,`' . $this->contactTableName . '` WHERE `'
			. $this->contactTableName . '`.`addressbookid` = \'' . \OCP\User::getUser() . '\' AND `'
			. $this->indexTableName . '`.`contactid` = `' . $this->contactTableName . '`.`id` AND `' 
			. $this->indexTableName . '`.`contactid` IN (' . join(',', array_fill(0, count($ids), '?')) . ')';
		
		$stmt = \OCP\DB::prepare($query);
		$result = $stmt->execute($ids);
	}
	
	while( $row = $result->fetchRow()) {
	    $this->getProperty($results, $row);
	}
	return $results;
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
    
    private function getProperty(&$results, $row) {
		if(!$row['name'] || !$row['value']) {
			return false;
		}

		$value = null;

		switch($row['name']) {
			case 'PHOTO':
				$value = 'VALUE=uri:' . \OCP\Util::linkToAbsolute('contacts', 'photo.php') . '?id=' . $row['contactid'];
				break;
			case 'N':
			case 'ORG':
			case 'ADR':
			case 'GEO':
			case 'CATEGORIES':
				$property = \Sabre\VObject\Property::create($row['name'], $row['value']);
				$value = $property->getParts();
				break;
			default:
				$value = $value = strtr($row['value'], array('\,' => ',', '\;' => ';'));
				break;
		}
		
		if(in_array($row['name'], Properties::$multiProperties)) {
			if(!isset($results[$row['contactid']])) {
				$results[$row['contactid']] = array('id' => $row['contactid'], $row['name'] => array($value));
			} elseif(!isset($results[$row['contactid']][$row['name']])) {
				$results[$row['contactid']][$row['name']] = array($value);
			} else {
				$results[$row['contactid']][$row['name']][] = $value;
			}
		} else {
			if(!isset($results[$row['contactid']])) {
				$results[$row['contactid']] = array('id' => $row['contactid'], $row['name'] => $value);
			} elseif(!isset($results[$row['contactid']][$row['name']])) {
				$results[$row['contactid']][$row['name']] = $value;
			}
		}
	}

}
