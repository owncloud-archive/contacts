<?php

/**
 * ownCloud - user_ldap
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

\OCP\User::checkLoggedIn();
\OCP\App::checkAppEnabled('contacts');

OCP\Util::addscript('contacts', 'personalsettings');
OCP\Util::addstyle('contacts', 'personalsettings');

// fill template
$tmpl = new OCP\Template('contacts', 'personalsettings');

$ldap = new OCA\Contacts\Backend\Ldap();
$ldapArray = $ldap->getAllAddressBooksForUser(\OCP\User::getUser());

$tmpl->assign('ldapArray', $ldapArray);

return $tmpl->fetchPage();
