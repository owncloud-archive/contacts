<?php
/**
 * ownCloud - VObject Group Property
 *
 * @author Thomas Tanghus
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Contacts\VObject;

use OC\VObject\CompoundProperty;

/**
 * This class adds convenience methods for the CATEGORIES property.
 *
 * NOTE: Group names are case-insensitive.
 */
class GroupProperty extends CompoundProperty {

	/**
	* Add a group.
	*
	* NOTE: We cannot just use add() as that method name is used in
	* \Sabre\VObject\Property
	*
	* @param string $name
	*/
	public function addGroup($name) {
		if($this->hasGroup($name)) {
			return;
		}
		$groups = $this->getParts();
		$groups[] = $name;
		$this->setParts($groups);
	}

	/**
	* Remove an existing group.
	*
	* @param string $name
	*/
	public function removeGroup($name) {
		if(!$this->hasGroup($name)) {
			return;
		}
		$groups = $this->getParts();
		array_splice($groups, $this->array_searchi($name, $groups), 1);
		$this->setParts($groups);
	}

	/**
	* Test it a group by that name exists.
	*
	* @param string $name
	* @return bool
	*/
	public function hasGroup($name) {
		return $this->in_arrayi($name, $this->getParts());
	}

	/**
	* Rename an existing group.
	*
	* @param string $from
	* @param string $to
	*/
	public function renameGroup($from, $to) {
		if(!$this->hasGroup($from)) {
			return;
		}
		$groups = $this->getParts();
		$groups[$this->array_searchi($from, $groups)] = $to;
		$this->setParts($groups);
	}

	// case-insensitive in_array
	protected function in_arrayi($needle, $haystack) {
		if(!is_array($haystack)) {
			return false;
		}
		return in_array(strtolower($needle), array_map('strtolower', $haystack));
	}

	// case-insensitive array_search
	protected function array_searchi($needle, $haystack) {
		if(!is_array($haystack)) {
			return false;
		}
		return array_search(strtolower($needle), array_map('strtolower', $haystack));
	}
}