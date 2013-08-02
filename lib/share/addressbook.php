<?php
/**
 * ownCloud
 *
 * @author Michael Gapczynski
 * @copyright 2013 Michael Gapczynski mtgap@owncloud.com
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
 */

namespace OCA\Contacts\Share;

use OC\Share\Share;

/**
 * Data holder for shared address books
 */
class AddressBook extends Share {

	protected $description;
	protected $lastmodified;
	protected $uri;

	public function __construct() {
		$this->addType('itemSource', 'int')
		$this->addType('lastmodified', 'int');
	}

	/**
	 * Get the description of the address book
	 * @return string
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * Set the description of the address book
	 * @param string $description
	 */
	public function setDescription($description) {
		$this->description = $description;
	}

	/**
	 * Get the last modified time of the address book
	 * @return int
	 */
	public function getLastmodified() {
		return $this->lastmodified;
	}

	/**
	 * Set the last modified time of the address book
	 * @param int $lastmodified
	 */
	public function setLastmodified($lastmodified) {
		$this->lastmodified = $lastmodified;
	}

	/**
	 * Get the uri of the address book
	 * @return string
	 */
	public function getUri() {
		return $this->uri;
	}

	/**
	 * Set the uri of the address book
	 * @param string $uri
	 */
	public function setUri($uri) {
		$this->uri = $uri;
	}

	/**
	 * Get the address book in the form of an array as expected by the contacts backend
	 * @return array
	 */
	public function toAddressBook() {
		return array(
			'id' => $this->getItemSource(),
			'displayname' => $this->getItemTarget(),
			'description' => $this->getDescription(),
			'owner' => $this->getItemOwner(),
			'lastmodified' => $this->getLastmodified(),
			'uri' => $this->getUri(),
			'permissions' => $this->getPermissions(),
			'backend' => 'shared',
		);
	}

}