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

use Sabre\VObject\Component,
	Sabre\VObject\StringUtil,
	\SplFileObject as SplFileObject;

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
					// Create a new property and attach it to the vcard
					$property = $this->getOrCreateVCardProperty($vcard, $importEntry->vcard_entry);
					$this->updateProperty($property, $importEntry, $element[$i]);
				} else {
					$property = \Sabre\VObject\Property::create("X-Unknown-Element", $element[$i]);
					$property->parameters[] = new \Sabre\VObject\Parameter('TYPE', ''.StringUtil::convertToUTF8($title[$i]));
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
	
	/**
	 * @brief calculates a percentage of the correspondance of the current format with the given element.
	 * @param $element the element to calculate
	 * @return number between 0 and 1, result of the formula (number of fields-number of X-Unkown-Element)/number of fields
	 */
	public function getFormatMatch($element) {
		$fieldsNumber = count($element->getChildren);
		$unkownNumber = count($element->select("X-Unknown-Element"));
		
		return (($fieldsNumber-$unkownNumber)/$fieldsNumber);
	}
	
}

?>
