<?php
/**
 * Copyright (c) 2012, 2013 Thomas Tanghus <thomas@tanghus.net>
 * Copyright (c) 2011 Jakob Sack mail@jakobsack.de
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Contacts;

// Check if we are a user
\OCP\User::checkLoggedIn();
\OCP\App::checkAppEnabled('contacts');

\OCP\App::setActiveNavigationEntry('contacts_index');

$impp_types = Utils\Properties::getTypesForProperty('IMPP');
$adr_types = Utils\Properties::getTypesForProperty('ADR');
$phone_types = Utils\Properties::getTypesForProperty('TEL');
$email_types = Utils\Properties::getTypesForProperty('EMAIL');
$ims = Utils\Properties::getIMOptions();
$im_protocols = array();
foreach($ims as $name => $values) {
	$im_protocols[$name] = $values['displayname'];
}

$maxUploadFilesize = \OCP\Util::maxUploadFilesize('/');

\OCP\Util::addScript('', 'jquery.multiselect');
\OCP\Util::addScript('', 'oc-vcategories');
\OCP\Util::addScript('contacts', 'jquery.combobox');
\OCP\Util::addScript('contacts', 'modernizr.custom');
\OCP\Util::addScript('contacts', 'app');
\OCP\Util::addScript('contacts', 'addressbooks');
\OCP\Util::addScript('contacts', 'contacts');
\OCP\Util::addScript('contacts', 'storage');
\OCP\Util::addScript('contacts', 'groups');
\OCP\Util::addScript('contacts', 'jquery.ocaddnew');
\OCP\Util::addScript('files', 'jquery.fileupload');
\OCP\Util::addScript('3rdparty/Jcrop', 'jquery.Jcrop');
\OCP\Util::addStyle('3rdparty/fontawesome', 'font-awesome');
\OCP\Util::addStyle('contacts', 'font-awesome');
\OCP\Util::addStyle('', 'jquery.multiselect');
\OCP\Util::addStyle('contacts', 'jquery.combobox');
\OCP\Util::addStyle('contacts', 'jquery.ocaddnew');
\OCP\Util::addStyle('3rdparty/Jcrop', 'jquery.Jcrop');
\OCP\Util::addStyle('contacts', 'contacts');

$tmpl = new \OCP\Template( "contacts", "contacts", "user" );
$tmpl->assign('uploadMaxFilesize', $maxUploadFilesize);
$tmpl->assign('uploadMaxHumanFilesize',
	\OCP\Util::humanFileSize($maxUploadFilesize), false);
$tmpl->assign('phone_types', $phone_types);
$tmpl->assign('email_types', $email_types);
$tmpl->assign('adr_types', $adr_types);
$tmpl->assign('impp_types', $impp_types);
$tmpl->assign('im_protocols', $im_protocols);
$tmpl->printPage();
