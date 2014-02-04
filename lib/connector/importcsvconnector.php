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
use Sabre\VObject\StringUtil;

class ImportCsvConnector extends ImportConnector {

	/**
	 * @brief separates elements from the input stream according to the entry_separator value in config
	 * ignoring the first line if mentionned in the config
	 * @param $input the input file to import
	 * @param $limit the number of elements to return (-1 = no limit)
	 * @return array of strings
	 */
	public function getElementsFromInput($file, $limit=-1) {
		
		$linesAndTitles = $this->getSourceElementsFromFile($file, $limit);
		$lines = $linesAndTitles[0];
		$titles = $linesAndTitles[1];
		$elements = array();
		foreach ($lines as $line) {
			$elements[] = $this->convertElementToVCard($line, $titles);
		}
		
		return array_values($elements);
	}
	
	private function getSourceElementsFromFile($file, $limit=-1) {
		$csv = new SplFileObject($file, 'r');
		$csv->setFlags(SplFileObject::READ_CSV);
		
		if (isset($this->configContent->import_core->delimiter)) {
			$csv->setCsvControl((string)$this->configContent->import_core->delimiter);
		}
		
		$ignore_first_line = (isset($this->configContent->import_core->ignore_first_line) && $this->configContent->import_core->ignore_first_line['enabled'] == 'true');
		
		$titles = false;
		
		$lines = array();
		
		$index = 0;
		foreach($csv as $line)
		{
			if (!($ignore_first_line && $index == 0) && count($line) > 1) { // Ignore first line
				
				$lines[] = $line;
				
				if (count($lines) == $limit) {
					break;
				}
			} else if ($ignore_first_line && $index == 0) {
				$titles = $line;
			}
			$index++;
		}
		
		return array($lines, $titles);
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
				if (isset($this->configContent->import_core->base_parsing)) {
					if (strcasecmp((string)$this->configContent->import_core->base_parsing, 'position') == 0) {
						$importEntry = $this->getImportEntryFromPosition((String)$i);
					} else if (strcasecmp((string)$this->configContent->import_core->base_parsing, 'name') == 0) {
						$importEntry = $this->getImportEntryFromName($title[$i]);
					}
				}
				if ($importEntry) {
					// Create a new property and attach it to the vcard
					$property = $this->getOrCreateVCardProperty($vcard, $importEntry->vcard_entry);
					$this->updateProperty($property, $importEntry, $element[$i]);
				} else {
					$property = \Sabre\VObject\Property::create("X-Unknown-Element", StringUtil::convertToUTF8($element[$i]));
					$property->parameters[] = new \Sabre\VObject\Parameter('TYPE', ''.StringUtil::convertToUTF8($title[$i]));
					$vcard->add($property);
				}
			}
		}
		$vcard->validate(\Sabre\VObject\Component\VCard::REPAIR);
		return $vcard;
	}
	
	private function getImportEntryFromPosition($position) {
		for ($i=0; $i < $this->configContent->import_entry->count(); $i++) {
			if ($this->configContent->import_entry[$i]['position'] == $position && $this->configContent->import_entry[$i]['enabled'] == 'true') {
				return $this->configContent->import_entry[$i];
			}
		}
		return false;
	}
	
	private function getImportEntryFromName($name) {
		for ($i=0; $i < $this->configContent->import_entry->count(); $i++) {
			if ($this->configContent->import_entry[$i]['name'] == StringUtil::convertToUTF8($name) && $this->configContent->import_entry[$i]['enabled'] == 'true') {
				return $this->configContent->import_entry[$i];
			}
			if (isset($this->configContent->import_entry[$i]->altname)) {
				foreach ($this->configContent->import_entry[$i]->altname as $altname) {
					if ($altname == StringUtil::convertToUTF8($name) && $this->configContent->import_entry[$i]['enabled'] == 'true') {
						return $this->configContent->import_entry[$i];
					}
				}
			}
		}
		return false;
	}
	
	/**
	 * @brief returns the probability that the first element is a match for this format
	 * @param $file the file to examine
	 * @return 0 if not a valid csv file
	 *         1 - 0.5*(number of untranslated elements/total number of elements)
	 * The more the first element has untranslated elements, the more the result is close to 0.5
	 */
	public function getFormatMatch($file) {
		// Examining the first element only
		$partsAndTitle = $this->getSourceElementsFromFile($file, 1);
		$parts = $partsAndTitle[0];
		$titles = $partsAndTitle[1];

		if (!$parts || ($parts && isset($this->configContent->import_core->expected_columns) && count($parts[0]) != (string)$this->configContent->import_core->expected_columns)) {
			// Doesn't look like a csv file
			return 0;
		} else {
			$element = $this->convertElementToVCard($parts[0], $titles);

			$unknownElements = $element->select("X-Unknown-Element");
			//error_log($this->configContent->import_core->name." - ".count($unknownElements)."/".count($parts[0]));
			return (1 - (0.5 * count($unknownElements)/count($parts[0])));
		}
	}
}

?>
