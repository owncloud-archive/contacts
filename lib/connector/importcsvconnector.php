<?php
/**
 * ownCloud - CSV Import connector
 *
 * @author Nicolas Mora
 * @copyright 2013-2014 Nicolas Mora mail@babelouest.org
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
use \SplFileObject as SplFileObject;

class ImportCsvConnector extends ImportConnector{

	/**
	 * @brief separates elements from the input stream according to the entry_separator value in config
	 * ignoring the first line if mentionned in the config
	 * @param $input the input file to import
	 * @param $limit the number of elements to return (-1 = no limit)
	 * @return array of strings
	 */
	public function getElementsFromInput($input, $limit=-1) {
		$csv = new SplFileObject($input, 'r');
		$csv->setFlags(SplFileObject::READ_CSV);
		
		//echo "elements: ".var_dump($this->configContent)."\n";
		//echo "exemple: ".$this->configContent->import_entry[2]->vcard_entry['property']."\n";
		//echo "settings : ".$this->configContent->import_core->card_separator['value']." - ".$this->configContent->import_core->entry_separator['value'];
		//$csv->setCsvControl($this->configContent->import_core->card_separator['value'], $this->configContent->import_core->entry_separator['value']);
		
		$ignore_first_line = (isset($this->configContent->import_core->ignore_first_line) && $this->configContent->import_core->ignore_first_line['enabled'] == 'true');
		
		$titles = null;
		
		$elements = array();
		
		$index = 0;
		foreach($csv as $line)
		{
			if (!($ignore_first_line && $index == 0) && count($line) > 1) { // Ignore first line
				
				$elements[] = $this->convertElementToVCard($line, $titles);
				
				if (count($elements) == $limit) {
					break;
				}
			} else if ($ignore_first_line && $index == 0) {
				$titles = $line;
			}
			$index++;
		}
		
		return array_values($elements);
	}
	
	/**
	 * @brief converts a unique element into a owncloud VCard
	 * @param $element the element to convert
	 * @return VCard, all unconverted elements are stored in X-Unknown-Element parameters
	 */
	public function convertElementToVCard($element, $title = null) {
		$vcard = \Sabre\VObject\Component::create('VCARD');
		
		for ($i=0; $i < count($element); $i++) {
			if ($element[$i] != '') {
				// Look for the right import_entry
				$importEntry = $this->getImportEntry((String)$i);
				if ($importEntry) {
					//$properties = $vcard->select($importEntry->vcard_entry['property']);
					//if (count($properties) == 0) {
						// Create a new property and attach it to the vcard
						$property = $this->getOrCreateVCardProperty($vcard, $importEntry->vcard_entry);
						//$property = \Sabre\VObject\Property::create($importEntry->vcard_entry['property']);
						$this->updateProperty($property, $importEntry, $element[$i]);
						//echo "property : ".$property->serialize();
						//$vcard->add($property);
					/*} else {
						for ($j=0; $j < count($properties); $j++) {
							$this->updateProperty($properties[$j], $importEntry, $element[$i]);
						}
					}*/
				} else {
					$property = \Sabre\VObject\Property::create("X-Unknown-Element", $element[$i]);
					$property->parameters[] = new \Sabre\VObject\Parameter('TYPE', ''.$title[$i]);
					$vcard->add($property);
				}
			}
		}
		$vcard->validate(\Sabre\VObject\Component\VCard::REPAIR);
		return $vcard;
	}
	
	private function getImportEntry($position) {
		for ($i=0; $i < $this->configContent->import_entry->count(); $i++) {
			if ($this->configContent->import_entry[$i]['position'] == $position && $this->configContent->import_entry[$i]['enabled'] == 'true') {
				return $this->configContent->import_entry[$i];
			}
		}
		return false;
	}
	
	private function updateProperty(&$property, $importEntry, $value) {
		if (isset($importEntry->vcard_entry['type'])) {
			$property->parameters[] = new \Sabre\VObject\Parameter('TYPE', ''.$importEntry->vcard_entry['type']);
		}
		if (isset($importEntry->vcard_entry['position'])) {
			$position = $importEntry->vcard_entry['position'];
			//echo "$value position $position\n";
			$v_array = explode(";", $property);
			$v_array[intval($position)] = $value;
			$property->setValue(implode(";", $v_array));
		} else {
			$property->setValue($value);
		}
	}
	
	/**
	 * @brief returns the vcard property corresponding to the ldif parameter
	 * creates the property if it doesn't exists yet
	 * @param $vcard the vcard to get or create the properties with
	 * @param $v_param the parameter the find
	 */
	public function getOrCreateVCardProperty(&$vcard, $v_param) {
		
		// looking for one
		//OCP\Util::writeLog('ldap_vcard_connector', __METHOD__.' entering '.$vcard->serialize(), \OCP\Util::DEBUG);
		$properties = $vcard->select($v_param['property']);
		foreach ($properties as $property) {
			//echo "update prop ".$v_param['property']."\n";
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
		//echo "create prop ".$v_param['property']."\n";
		
		
		// Property not found, creating one
		//OCP\Util::writeLog('ldap_vcard_connector', __METHOD__.', create one '.$v_param['property'].';TYPE='.$v_param['type'], \OCP\Util::DEBUG);
		$line = count($vcard->children) - 1;
		$property = \Sabre\VObject\Property::create($v_param['property']);
		$vcard->add($property);
		if ($v_param['type']!=null) {
			//OCP\Util::writeLog('ldap_vcard_connector', __METHOD__.', creating one '.$v_param['property'].';TYPE='.$v_param['type'], \OCP\Util::DEBUG);
			//\OC_Log::write('ldapconnector', __METHOD__.', creating one '.$v_param['property'].';TYPE='.$v_param['type'], \OC_Log::DEBUG);
			$property->parameters[] = new \Sabre\VObject\Parameter('TYPE', ''.$v_param['type']);
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
	
}

?>
