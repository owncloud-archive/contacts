<?php
/**
 * ownCloud - Import manager
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
 
namespace OCA\Contacts;
use Sabre\VObject\Component;
use OCA\Contacts\Connector\ImportCsvConnector;
use OCA\Contacts\Connector\ImportVCardConnector;
use OCA\Contacts\Addressbook;

/**
 * Manages the import with basic functionalities
 */
class ImportManager {
	
	private $importTypes;
	
	public function __construct() {
	}
	
	public function getTypes() {
		$prefix = "import_";
		$suffix = ".xml";
		$path = __DIR__ . "/../formats/";
		$files = scandir($path);
		$formats = array();
		foreach ($files as $file) {
			if (!strncmp($file, $prefix, strlen($prefix)) && substr($file, - strlen($suffix)) === $suffix) {
				$format = simplexml_load_file ( $path . $file );
				if ($format) {
					if (isset($format->import_core)
					&& isset($format->import_core->name)
					&& isset($format->import_core->display_name)
					&& isset($format->import_core->type)
					&& isset($format->import_core->active)
					&& $format->import_core->active == '1') {
						$formats[(string)$format->import_core->name] = (string)$format->import_core->display_name;
					}
				}
			}
		}
		return $formats;
	}
	
	/**
	 * @brief get all the preferences for the addressbook
	 * @param string $id
	 * @return array Format array('param1' => 'value', 'param2' => 'value')
	 */
	public function getType($typeName) {
		$path = __DIR__ . "/../formats/import_" . $typeName . "_connector.xml";
		if (file_exists($path)) {
			$format = simplexml_load_file ( $path );
			if ($format) {
				if (isset($format->import_core)
				&& isset($format->import_core->name)
				&& isset($format->import_core->display_name)
				&& isset($format->import_core->type)
				&& isset($format->import_core->active)
				&& $format->import_core->active == '1') {
					return $format;
				}
			}
		}
		return false;
	}
	
	/**
	 * @brief sets the preferences for the addressbook given in parameter
	 * @param string $id
	 * @param array $settings the preferences, format array('param1' => 'value', 'param2' => 'value')
	 * @return boolean
	 
	public function setType($typeName, $settings) {
		$types = (array)$this->getTypes();

		if (!in_array($typeName, $types)) {
			$types[] = $typeName;
		}
		$encodedTypes = json_encode($types);
		
		\OCP\Config::setAppValue('contacts', 'import_types', $encodedTypes);
		
		$key = 'import_' . $typeName;

		$data = json_encode($settings);
		//echo "types : $encodedTypes\ndata : $data\n";
		return $data
			? \OCP\Config::setAppValue('contacts', $key, $data)
			: false;
	}*/
	
	/**
	 * @brief imports the file with the selected type, and converts into VCards
	 * @param $file the path to the file
	 * @param $typeName the type name to use as stored into the app settings
	 * @param $limit the number of elements to import
	 * @return an array containing VCard elements|false if empty of error
	 */
	public function importFile($file, $typeName, $limit=-1) {
		$importType = $this->getType($typeName);
		$elements = array();
		if ((string)$importType->import_core->type == 'csv') {
			// use class ImportCsvConnector
			$connector = new ImportCsvConnector($importType);
			$elements = $connector->getElementsFromInput($file, $limit);
		} else if ((string)$importType->import_core->type == 'vcard') {
			// use class importVcardConnector
			$connector = new ImportVCardConnector($importType);
			$elements = $connector->getElementsFromInput($file, $limit);
		}
		if (count($elements) > 0) {
			return $elements;
		} else {
			return false;
		}
	}
	
	/**
	 * @brief import the first element of the file with all the types
	 * detects wich imported type has the least elements "X-Unknown-Element"
	 * then returns the corresponding type
	 * @param $file the path to the file
	 * @return the corresponding type|false
	 */
	public function detectFileType($file) {
	}
	
	/**
	 * @brief Query whether a backend or an address book is active
	 * @param string $addressbookid If null it checks whether the backend is activated.
	 * @return boolean
	 
	public function isActive($typeName = null) {
		$key = 'active_import_' . $typeName;

		return !!(\OCP\Config::getAppValue('contacts', $key, 1));
	}*/

	/**
	 * @brief Activate a backend or an address book
	 * @param bool active
	 * @param string $addressbookid If null it activates the backend.
	 * @return boolean
	 
	public function setActive($active, $typeName = null) {
		$key = 'active_import_' . $typeName;

		$this->setModifiedAddressBook($typeName);
		return \OCP\Config::getAppValue('contacts', $key, (int)$active);
	}*/
	
}

?>
