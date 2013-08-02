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
use OC\Share\ShareBackend;
use OC\Share\TimeMachine;
use OC\Share\Exception\InvalidShareException;
use OCA\Contacts\Backend\Shared;

class AddressBookShareBackend extends ShareBackend {

	/**
	 * @var \OCA\Contacts\Backend\Database
	 */
	protected $backend;

	/**
	 * The constructor
	 * @param \OC\Share\TimeMachine $timeMachine The time() mock
	 * @param \OC\Share\ShareType\IShareType[] $shareTypes An array of share type objects that
	 * items can be shared through e.g. User, Group, Link
	 * @param \OCA\Contacts\Backend\Database $backend
	 */
	public function __construct(TimeMachine $timeMachine, array $shareTypes, Database $backend) {
		parent::__construct($timeMachine, $shareTypes);
		$this->backend = $backend;
	}

	/**
	 * Get the identifier for the item type this backend handles
	 * @return string
	 */
	public function getItemType() {
		return 'addressbooks';
	}

	/**
	 * Check if an item is valid for the share owner
	 * @param \OC\Share\Share $share
	 * @throws \OC\Share\Exception\InvalidItemException If the address book does not exist or the
	 * share owner does not have access to the address book
	 * @return bool
	 */
	protected function isValidItem(Share $share) {
		$addressbook = $this->backend->getAddressBook($share->getItemSource());
		if (!$addressbook) {
			throw new InvalidItemException('The address book does not exist or is not accessible');
		}
		return true;
	}

}