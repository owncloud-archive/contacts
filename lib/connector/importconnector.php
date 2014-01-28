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

use Sabre\VObject\Component,
	Sabre\VObject\StringUtil;

abstract class ImportConnector {

	public $name;
	
	protected $configContent;
	
	public function __construct($xml_config = null) {
		if ($xml_config != null) {
			$this->setConfig($xml_config);
		}
	}
	
	abstract function getElementsFromInput($input, $limit=-1);
	
	abstract function convertElementToVCard($element);
	
	abstract function getFormatMatch($elements);
	
	public function setConfig($xml_config) {
		$this->configContent = $xml_config;
	}
	
	protected function updateProperty(&$property, $importEntry, $value) {
		if (isset($importEntry->vcard_entry['type'])) {
			$property->parameters[] = new \Sabre\VObject\Parameter('TYPE', ''.StringUtil::convertToUTF8($importEntry->vcard_entry['type']));
		}
		if (isset($importEntry->vcard_entry->additional_property)) {
			foreach ($importEntry->vcard_entry->additional_property as $additionalProperty) {
				$property->parameters[] = new \Sabre\VObject\Parameter(''.$additionalProperty['name'], ''.$additionalProperty['value']);
			}
		}
		if (isset($importEntry->vcard_entry['prefix'])) {
			$value = $importEntry->vcard_entry['prefix'].$value;
		}
		if (isset($importEntry->vcard_entry['position'])) {
			$position = $importEntry->vcard_entry['position'];
			$v_array = explode(";", $property);
			$v_array[intval($position)] = StringUtil::convertToUTF8($value);
			$property->setValue(implode(";", $v_array));
		} else {
			$property->setValue(StringUtil::convertToUTF8($value));
		}
	}
	
	/**
	 * @brief returns the vcard property corresponding to the ldif parameter
	 * creates the property if it doesn't exists yet
	 * @param $vcard the vcard to get or create the properties with
	 * @param $v_param the parameter the find
	 */
	protected function getOrCreateVCardProperty(&$vcard, $v_param) {
		
		// looking for one
		//OCP\Util::writeLog('ldap_vcard_connector', __METHOD__.' entering '.$vcard->serialize(), \OCP\Util::DEBUG);
		$properties = $vcard->select($v_param['property']);
		foreach ($properties as $property) {
			//echo "update prop ".$v_param['property']."\n";
			if ($v_param['type'] == null && !isset($v_param->additional_property)) {
				//OCP\Util::writeLog('ldap_vcard_connector', __METHOD__.' property '.$v_param['type'].' found', \OCP\Util::DEBUG);
				return $property;
			}
			foreach ($property->parameters as $parameter) {
				//OCP\Util::writeLog('ldap_vcard_connector', __METHOD__.' parameter '.$parameter->value.' <> '.$v_param['type'], \OCP\Util::DEBUG);
				if (!strcmp($parameter->value, $v_param['type'])) {
					//OCP\Util::writeLog('ldap_vcard_connector', __METHOD__.' parameter '.$parameter->value.' found', \OCP\Util::DEBUG);
					$found=0;
					if (isset($v_param->additional_property)) {
						foreach($v_param->additional_property as $additional_property) {
							if ((string)$parameter->name == $additional_property['name']) {
								$found++;
							}
						}
						if ($found == count($v_param->additional_property)) {
							return $property;
						}
					}
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
			$property->parameters[] = new \Sabre\VObject\Parameter('TYPE', ''.StringUtil::convertToUTF8($v_param['type']));
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
