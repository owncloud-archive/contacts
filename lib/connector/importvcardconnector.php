<?php
/**
 * ownCloud - VCard Import connector
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
	\SplFileObject as SplFileObject,
	Sabre\VObject;

/**
 * @brief Implementation of the VCard import format
 */
class ImportVCardConnector extends ImportConnector{

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
		foreach($parts as $part) {
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
		$file = StringUtil::convertToUTF8(file_get_contents($file));

		$nl = "\n";
		$replace_from = array("\r","\n\n");
		$replace_to = array("\n","\n");
		foreach ($this->configContent->import_core->replace as $replace) {
			if (isset($replace['from']) && isset($replace['to'])) {
				$replace_from[] = $replace['from'];
				$replace_to[] = $replace['to'];
			}
		}
		
		$file = str_replace($replace_from, $replace_to, $file);
		
		$lines = explode($nl, $file);
		$inelement = false;
		$parts = array();
		$card = array();
		$numParts = 0;
		foreach($lines as $line) {
				if(strtoupper(trim($line)) == (string)$this->configContent->import_core->card_begin) {
						$inelement = true;
				} elseif (strtoupper(trim($line)) == (string)$this->configContent->import_core->card_end) {
						$card[] = $line;
						$parts[] = implode($nl, $card);
						$card = array();
						$inelement = false;
						$numParts++;
						if ($numParts == $limit) {
							break;
						}
				}
				if ($inelement === true && trim($line) != '') {
						$card[] = $line;
				}
		}
		return $parts;
	}
	
	/**
	 * @brief converts a VCard into a owncloud VCard
	 * @param $element the VCard element to convert
	 * @return VCard
	 */
	public function convertElementToVCard($element) {
		$source = VObject\Reader::read($element);
		$dest = \Sabre\VObject\Component::create('VCARD');
		
		foreach ($source->children() as $sourceProperty) {
			$importEntry = $this->getImportEntry($sourceProperty, $source);
			if ($importEntry) {
				$value = $sourceProperty->value;
				if (isset($importEntry['remove'])) {
					$value = str_replace($importEntry['remove'], '', $sourceProperty->value);
				}
				$values = array($value);
				if (isset($importEntry['separator'])) {
					$values = explode($importEntry['separator'], $value);
				}
				
				foreach ($values as $oneValue) {
					if (isset($importEntry->vcard_favourites)) {
						foreach ($importEntry->vcard_favourites as $vcardFavourite) {
							if (strcasecmp((string)$vcardFavourite, trim($oneValue)) == 0) {
								$property = \Sabre\VObject\Property::create("X-FAVOURITES", 'yes');
								$dest->add($property);
							} else {
								$property = $this->getOrCreateVCardProperty($dest, $importEntry->vcard_entry);
								$this->updateProperty($property, $importEntry, trim($oneValue));
							}
						}
					} else {
						$property = $this->getOrCreateVCardProperty($dest, $importEntry->vcard_entry);
						$this->updateProperty($property, $importEntry, $sourceProperty->value);
					}
				}
			} else {
				$property = clone $sourceProperty;
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
	private function getImportEntry($property, $vcard) {
		for ($i=0; $i < $this->configContent->import_entry->count(); $i++) {
			if ($this->configContent->import_entry[$i]['property'] == $property->name && $this->configContent->import_entry[$i]['enabled'] == 'true') {
				if (isset($this->configContent->import_entry[$i]->group_entry)) {
					$toUnset = array();
					$numElt = 0;
					foreach($this->configContent->import_entry[$i]->group_entry as $groupEntry) {
						$sourceGroupList = $vcard->select($groupEntry['property']);
						if (count($sourceGroupList>0)) {
							foreach ($sourceGroupList as $oneSourceGroup) {
								if ($oneSourceGroup->value == $groupEntry['value'] && isset($oneSourceGroup->group) && isset($property->group) && $oneSourceGroup->group == $property->group) {
									$numElt++;
								}
							}
						}
					}
					if ($numElt == count($this->configContent->import_entry[$i]->group_entry)) {
						return $this->configContent->import_entry[$i];
					}
				} else {
					return $this->configContent->import_entry[$i];
				}
			}
		}
		return false;
	}
	
	/**
	 * @brief returns the probability that the first element is a match for this format
	 * @param $file the file to examine
	 * @return 0 if not a valid vcard
	 *         1-0.5^(number of translated elements+1)
	 * The more the first element has parameters to translate, the more the result is close to 1
	 */
	public function getFormatMatch($file) {
		// Examining the first element only
		$parts = $this->getSourceElementsFromFile($file, 1);
		
		if (!$parts || ($parts && count($parts) == 0)) {
			// Doesn't look like a vcf file
			return 0;
		} else {
			try {
				$vcard = VObject\Reader::read($parts[0]);
			} catch (VObject\ParseException $e) {
				// error while parsing, doesn't look like a vcard
				return 0;
			}
			$toTranslate=1;
			foreach ($vcard->children() as $vcardProperty) {
				$importEntry = $this->getImportEntry($vcardProperty, $vcard);
				if ($importEntry) {
					$toTranslate++;
				}
			}
			return (1 - pow(0.5, $toTranslate));
		}
	}
	
}

?>
