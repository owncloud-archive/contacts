<?php
/**
 * ownCloud - CSV Import connector
 *
 * @author Nicolas Mora
 * @copyright 2013 Nicolas Mora mail@babelouest.org
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation
 * version 3 of the License
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.	If not, see <http://www.gnu.org/licenses/>.
 *
 */
 
namespace OCA\Contacts\Connector;
use Sabre\VObject\Component;

class ImportCsvConnector extends ImportConnector {

	/**
	 * @brief separates elements from the input stream according to the entry_separator value in config
	 * ignoring the first line if mentionned in the config
	 * @param $input the input file to import
	 * @param $limit the number of elements to return (-1 = no limit)
	 * @return array of strings
	 */
	public getElementsFromInput($input, $limit=-1) {
		$csv = new SplFileObject($input, 'r');
		$csv->setFlags(SplFileObject::READ_CSV);
		$csv->setCsvControl($this->configContent->import_entries->import_core->control['separator'], $this->configContent->import_entries->import_core->control['enclosure'], $this->configContent->import_entries->import_core->control['enclosure']);
		
		$elements = array();
		
		foreach($csv as $line)
		{
			$elements[] = convertElementToVCard($line);
		}
		$elements = array();
		
		if (($limit > -1) && ($this->configContent->import_entries->import_core->ignore_first_line['enabled'] == 'true')) {
			$limit++;
		}
		
		return array_values($elements);
	}
	
	/**
	 * @brief converts a unique element into a owncloud VCard
	 * @param $element the element to convert
	 * @return VCard, all unconverted elements are stored in X-Unknown-Element parameters
	 */
	public convertElementToVCard($element) {
		$vcard = \Sabre\VObject\Component::create('VCARD');
		
		for ($i=0; $i < count($element); $i++) {
			// Look for the right import_entry
			$importEntry = getImportEntry((String)$i);
			if ($importEntry) {
				$properties = $vcard->select($importEntry->vcard_entry['property']);
				if (count($properties) == 0) {
					// Create a new property and attach it to the vcard
					$property = $this->getOrCreateVCardProperty($vcard, $importEntry)
					$property = \Sabre\VObject\Property::create($importEntry->vcard_entry['property']);
					updateProperty($property, $importEntry, $element[$i]);
					$vcard->add($property);
				} else {
					for ($j=0; $j < count($properties); $j++) {
					}
				}
			} else {
				$property = \Sabre\VObject\Property::create("X-Unknown-Element", $element[$i]);
				$vcard->add($property);
			}
		}
	}
	
	private getImportEntry($position) {
		for ($i=0; $i < count($this->configContent->import_entries->import_entry); $i++) {
			if ($this->configContent->import_entries->import_entry[$i]['position'] == $position) {
				return $this->configContent->import_entries->import_entry[$i];
			}
		}
		return false;
	}
	
	private updateProperty(&$property, $importEntry, $value) {
		if (is_set($importEntry->vcard_entry['type'])) {
			$property->add('TYPE', $importEntry->vcard_entry['type']);
		}
	}
	
	/**
	 * @brief transform a ldap entry into an VCard object
	 *	for each ldap entry which is like "property: value"
	 *	to a VCard entry which is like "PROPERTY[;PARAMETER=param]:value"
	 * @param array $ldap_entry
	 * @return OC_VCard
	 */
	public function toVCard($entry) {
		$vcard = \Sabre\VObject\Component::create('VCARD');
		$vcard->REV = $ldapEntry['modifytimestamp'][0];
		// OCP\Util::writeLog('ldap_vcard_connector', __METHOD__.' vcard is '.$vcard->serialize(), \OCP\Util::DEBUG);
		
		for ($i=0; $i<$ldapEntry["count"]; $i++) {
			// ldap property name : $ldap_entry[$i]
			$l_property = $ldapEntry[$i];
			for ($j=0;$j<$ldapEntry[$l_property]["count"];$j++){
				
				// What to do :
				// convert the ldap property into vcard property, type and position (if needed)
				// $v_params format: array('property' => property, 'type' => array(types), 'position' => position)
				$v_params = $this->getVCardProperty($l_property);
				
				foreach ($v_params as $v_param) {
					
					if (isset($v_param['unassigned'])) {
						// if the value comes from the unassigned entry, it's a vcard property dumped
						try {
							$property = \Sabre\VObject\Reader::read($ldapEntry[$l_property][$j]);
							$vcard->add($property);
						} catch (exception $e) {
						}
					} else {
						// Checks if a same kind of property already exists in the VCard (property and parameters)
						// if so, sets a property variable with the current data
						// else, creates a property variable
						$v_property = $this->getOrCreateVCardProperty($vcard, $v_param, $j);
						
						// modify the property with the new data
						if (strcasecmp($v_param['image'], 'true') == 0) {
							$this->updateVCardImageProperty($v_property, $ldapEntry[$l_property][$j], $vcard->VERSION);
						} else {
							$this->updateVCardProperty($v_property, $ldapEntry[$l_property][$j], $v_param['position']);
						}
					}
				}
			}
		}
		
		if (!isset($vcard->UID)) {
			$vcard->UID = base64_encode($ldapEntry['dn']);
		}
		$vcard->validate(\Sabre\VObject\Component\VCard::REPAIR);
		return $vcard;
	}
	
	/**
	 * @brief returns the vcard property corresponding to the ldif parameter
	 * creates the property if it doesn't exists yet
	 * @param $vcard the vcard to get or create the properties with
	 * @param $v_param the parameter the find
	 * @param $index the position of the property in the vcard to find
	 */
	public function getOrCreateVCardProperty(&$vcard, $v_param) {
		
		// looking for one
		//OCP\Util::writeLog('ldap_vcard_connector', __METHOD__.' entering '.$vcard->serialize(), \OCP\Util::DEBUG);
		$properties = $vcard->select($v_param['property']);
		foreach ($properties as $property) {
			if ($v_param['type'] == null) {
				//OCP\Util::writeLog('ldap_vcard_connector', __METHOD__.' property '.$v_param['type'].' found', \OCP\Util::DEBUG);
				return $property;
			}
			foreach ($property->parameters as $parameter) {
				//OCP\Util::writeLog('ldap_vcard_connector', __METHOD__.' parameter '.$parameter->value.' <> '.$v_param['type'], \OCP\Util::DEBUG);
				if (!strcmp($parameter->value, $v_param['type'])) {
					//OCP\Util::writeLog('ldap_vcard_connector', __METHOD__.' parameter '.$parameter->value.' found', \OCP\Util::DEBUG);
					return $property;
				}
			}
		}
		
		// Property not found, creating one
		//OCP\Util::writeLog('ldap_vcard_connector', __METHOD__.', create one '.$v_param['property'].';TYPE='.$v_param['type'], \OCP\Util::DEBUG);
		$line = count($vcard->children) - 1;
		$property = \Sabre\VObject\Property::create($v_param['property']);
		$vcard->add($property);
		if ($v_param['type']!=null) {
			//OCP\Util::writeLog('ldap_vcard_connector', __METHOD__.', creating one '.$v_param['property'].';TYPE='.$v_param['type'], \OCP\Util::DEBUG);
			//\OC_Log::write('ldapconnector', __METHOD__.', creating one '.$v_param['property'].';TYPE='.$v_param['type'], \OC_Log::DEBUG);
			$property->parameters[] = new	\Sabre\VObject\Parameter('TYPE', ''.$v_param['type']);
			switch ($v_param['property']) {
				case "ADR":
					//OCP\Util::writeLog('ldap_vcard_connector', __METHOD__.', we have an address '.$v_param['property'].';TYPE='.$v_param['type'], \OCP\Util::DEBUG);
					$property->setValue(";;;;;;");
					break;
				case "FN":
					$property->setValue(";;;;");
					break;
			}
		}
		//OCP\Util::writeLog('ldap_vcard_connector', __METHOD__.' exiting '.$vcard->serialize(), \OCP\Util::DEBUG);
		return $property;
	}
	
	/**
	 * @brief modifies a vcard property array with the ldap_entry given in parameter at the given position
	 */
	public function updateVCardProperty(&$v_property, $ldap_entry, $position=null) {
		for ($i=0; $i<count($v_property); $i++) {
			if ($position != null) {
				$v_array = explode(";", $v_property[$i]);
				$v_array[intval($position)] = $ldap_entry;
				$v_property[$i]->setValue(implode(";", $v_array));
			} else {
				$v_property[$i]->setValue($ldap_entry);
			}
		}
	}
	
}

?>
