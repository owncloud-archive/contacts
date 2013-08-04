<?php

/**
 * ownCloud - contacts personal settings
 *
 * @author Nicolas Mora
 * @copyright 2013 Nicolas Mora mail@babelouest.org
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

namespace OCA\Contacts\Ajax;

// Check user and app status
\OCP\User::checkLoggedIn();
\OCP\App::checkAppEnabled('contacts');
\OCP\JSON::callCheck();

switch($_GET['function']) {
	case 'list':
		$ldap = new OCA\Contacts\Backend\Ldap();
		$addressbooks = $ldap->getAllAddressBooksForUser(\OCP\User::getUser());
		\OCP\JSON::success(array('ldapArray' => $addressbooks));
		break;
	case 'details':
		$ldap = new OCA\Contacts\Backend\Ldap();
		$addressbooks = $ldap->getAllAddressBooksForUser(\OCP\User::getUser());
		\OCP\JSON::success(array('ldapArray' => $addressbooks));
		break
	case 'save':
		break;
	case 'delete':
		break;
	case 'test':
		break;
}
