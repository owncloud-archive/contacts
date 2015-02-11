<?php
/**
 * @author Lukas Reschke
 * @copyright 2015 Lukas Reschke lukas@owncloud.com
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Contacts\Factory;

use OCP\Util;

/**
 * @package OCA\Contacts\Factory
 */
class UtilFactory {
	/**
	 * calculates the maximum upload size respecting system settings, free space
	 * and user quota
	 *
	 * @param string $dir the current folder where the user currently operates
	 * @param int $free the number of bytes free on the storage holding $dir,
	 *                  if not set this will be received from the storage directly
	 * @return int number of bytes representing
	 */
	public function maxUploadFilesize($dir, $free = null) {
		return Util::maxUploadFilesize($dir, $free);
	}

	/**
	 * Make a human file size (2048 to 2 kB)
	 * @param int $bytes file size in bytes
	 * @return string a human readable file size
	 */
	public function humanFileSize($bytes) {
		return Util::humanFileSize($bytes);
	}
}
