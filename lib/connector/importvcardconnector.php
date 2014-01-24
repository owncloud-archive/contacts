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
	\SplFileObject as SplFileObject;

class ImportVCardConnector extends ImportConnector{

	/**
	 * @brief separates elements from the input stream according to the entry_separator value in config
	 * ignoring the first line if mentionned in the config
	 * @param $input the input file to import
	 * @param $limit the number of elements to return (-1 = no limit)
	 * @return array of strings
	 */
	public function getElementsFromInput($input, $limit=-1) {

		$file = file_get_contents($input);

		$nl = "\n";
		$file = str_replace(array("\r","\n\n"), array("\n","\n"), $file);
		$lines = explode($nl, $file);
		$inelement = false;
		$parts = array();
		$card = array();
		foreach($lines as $line) {
				if(strtoupper(trim($line)) == $this->configContent->import_core->card_begin['value']) {
						$inelement = true;
				} elseif (strtoupper(trim($line)) == $this->configContent->import_core->card_end['value']) {
						$card[] = $line;
						$parts[] = implode($nl, $card);
						$card = array();
						$inelement = false;
				}
				if ($inelement === true && trim($line) != '') {
						$card[] = $line;
				}
		}
		
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
	
}

?>
