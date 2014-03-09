<?php
/**
 * @author Thomas Tanghus
 * @copyright 2012-2014 Thomas Tanghus (thomas@tanghus.net)
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

$installedVersion = OCP\Config::getAppValue('contacts', 'installed_version');
if (version_compare($installedVersion, '0.2.5', '>=')) {
	// Set all address books active as (de)activating went awol at rewrite.
	$stmt = OCP\DB::prepare( 'UPDATE `*PREFIX*contacts_addressbooks` SET `active`= 1' );
	$result = $stmt->execute(array());
} elseif (version_compare($installedVersion, '0.2.4', '==')) {
	// First set all address books in-active.
	$stmt = OCP\DB::prepare( 'UPDATE `*PREFIX*contacts_addressbooks` SET `active`=0' );
	$result = $stmt->execute(array());

	// Then get all the active address books.
	$stmt = OCP\DB::prepare( 'SELECT `userid`,`configvalue` FROM `*PREFIX*preferences` WHERE `appid`=\'contacts\' AND `configkey`=\'openaddressbooks\'' );
	$result = $stmt->execute(array());

	// Prepare statement for updating the new 'active' field.
	$stmt = OCP\DB::prepare( 'UPDATE `*PREFIX*contacts_addressbooks` SET `active`=? WHERE `id`=? AND `userid`=?' );
	while ($row = $result->fetchRow()) {
		$ids = explode(';', $row['configvalue']);
		foreach ($ids as $id) {
			$r = $stmt->execute(array(1, $id, $row['userid']));
		}
	}

	// Remove the old preferences.
	$stmt = OCP\DB::prepare( 'DELETE FROM `*PREFIX*preferences` WHERE `appid`=\'contacts\' AND `configkey`=\'openaddressbooks\'' );
	$result = $stmt->execute(array());
}
