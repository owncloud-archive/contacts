<?php

/**
 * ownCloud - Updater plugin
 *
 * @author Nicolas Mora
 * @copyright 2014 Nicolas Mora mail@babelouest.org
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 */

namespace OCA\Updater;

\OCP\User::checkAdminUser();
$tmpl = new \OCP\Template('contacts', 'admin');
return $tmpl->fetchPage();