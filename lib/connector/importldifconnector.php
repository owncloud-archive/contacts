<?php
/**
 * ownCloud - CSV Import connector
 *
 * @author Nicolas Mora
 * @copyright 2014 Nicolas Mora mail@babelouest.org
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
	Sabre\VObject\StringUtil,
	Sabre\VObject;

/**
 * @brief Implementation of the VCard import format
 */
class ImportLdifConnector extends ImportConnector{

	/**
	 * @brief separates elements from the input stream according to the entry_separator value in config
	 * ignoring the first line if mentionned in the config
	 * @param $input the input file to import
	 * @param $limit the number of elements to return (-1 = no limit)
	 * @return array of strings
	 */
	public function getElementsFromInput($file, $limit=-1) {

		$parts = $this->getSourceElementsFromFile($file, $limit);
		
		$elements = array();
		foreach($parts as $part)
		{
			$elements[] = $this->convertElementToVCard($part);
		}
		
		return array_values($elements);
	}
	
	/**
	 * @brief parses the file in vcard format
	 * @param $file the input file to import
	 * @param $limit the number of elements to return (-1 = no limit)
	 * @return array()
	 */
	private function getSourceElementsFromFile($file, $limit=-1) {
		$file = file_get_contents($file);

		$nl = "\n";
		$replace_from = array("\r","\n\n","\n ");
		$replace_to = array("\n","\n","");
		foreach ($this->configContent->import_core->replace as $replace) {
			if (isset($replace['from']) && isset($replace['to'])) {
				$replace_from[] = $replace['from'];
				$replace_to[] = $replace['to'];
			}
		}
		
		$file = str_replace($replace_from, $replace_to, $file);
		
		$lines = explode($nl, $file);
		$parts = array();
		$card = array();
		$numParts = 0;
		foreach($lines as $line) {
			if (!preg_match("/^# /", $line)) { // Ignore comment line
				if(preg_match("/^\w+:: /",$line)) {
					$kv = explode(':: ', $line, 2);
					$key = $kv[0];
					$value = base64_decode($kv[1]);
				} else {
					$kv = explode(': ', $line, 2);
					$key = $kv[0];
					if(count($kv) == 2) {
						$value = $kv[1];
					} else {
						$value = "";
					}
				}
				if ($key == "dn") {
					if (count($card) > 0) {
						$parts[] = $card;
						$numParts++;
						if ($numParts == $limit) {
							break;
						}
					}
					$card = array(array($key, $value));
				} else if ($key != "") {
					$card[] = array($key, $value);
				}
			}
		}
		if ($numParts != $limit) {
			$parts[] = $card;
		}
		return $parts;
	}
	
	/**
	 * @brief converts a ldif into a owncloud VCard
	 * @param $element the VCard element to convert
	 * @return VCard
	 */
	public function convertElementToVCard($element) {
		$dest = \Sabre\VObject\Component::create('VCARD');
		
		foreach ($element as $ldifProperty) {
			$importEntry = $this->getImportEntry($ldifProperty[0]);
			if ($importEntry) {
				$property = $this->getOrCreateVCardProperty($dest, $importEntry->vcard_entry);
				if (isset($importEntry['image']) && $importEntry['image'] == "true") {
					$this->updateImageProperty($property, $ldifProperty[1]);
				} else {
					$this->updateProperty($property, $importEntry, $ldifProperty[1]);
				}
			} else {
				$property = \Sabre\VObject\Property::create("X-Unknown-Element", ''.StringUtil::convertToUTF8($ldifProperty[1]));
				$property->parameters[] = new \Sabre\VObject\Parameter('TYPE', ''.StringUtil::convertToUTF8($ldifProperty[0]));
				$dest->add($property);
			}
		}
		
		$dest->validate(\Sabre\VObject\Component\VCard::REPAIR);
		return $dest;
	}
	
	/**
	 * @brief tests if the property has to be translated by looking for its signature in the xml configuration
	 * @param $property Sabre VObject Property too look
	 * @param $vcard the parent Sabre VCard object to look for a 
	 */
	private function getImportEntry($property) {
		foreach ($this->configContent->import_entry as $importEntry) {
			if ($importEntry['name'] == $property) {
				return $importEntry;
			}
		}
		return false;
	}
	
	/**
	 * @brief returns the probability that the first element is a match for this format
	 * @param $file the file to examine
	 * @return 0 if not a valid ldif file
	 *         1 - 0.5*(number of untranslated elements/total number of elements)
	 * The more the first element has untranslated elements, the more the result is close to 0.5
	 */
	public function getFormatMatch($file) {
		// Examining the first element only
		$parts = $this->getSourceElementsFromFile($file, 1);

		if (!$parts) {
			// Doesn't look like a ldif file
			return 0;
		} else {
			$element = $this->convertElementToVCard($parts);
			$unknownElements = $element->select("X-Unknown-Element");
			return (1 - (0.5 * count($unknownElements)/count($parts[0])));
		}
	}	
}

?>
