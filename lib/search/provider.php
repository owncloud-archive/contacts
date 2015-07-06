<?php

/**
 * ownCloud
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

namespace OCA\Contacts\Search;

/**
 * The updated contacts search provider
 */
class Provider extends \OCP\Search\Provider {

	/**
	 * Search for contacts
	 *
	 * @param string $query
	 * @return array list of \OCA\Calendar\Search\Contact
	 */
	function search($query) {
		$_results = \OC::$server->getContactsManager()->search($query, ['N', 'FN', 'EMAIL', 'NICKNAME', 'ORG', 'PHOTO']);
		$results = array();
		foreach ($_results as $_result) {
			if ($_result['addressbook-key'] === 'local') {
				continue;
			}
			$results[] = new Contact($_result);
		}
		return $results;
	}
}
