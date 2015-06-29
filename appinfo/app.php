<?php
/**
 * @author Thomas Tanghus
 * @copyright 2011-2014 Thomas Tanghus (thomas@tanghus.net)
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Contacts;

\Sabre\VObject\Component\VCard::$componentMap['VCARD']	= '\OCA\Contacts\VObject\VCard';
\Sabre\VObject\Component\VCard::$propertyMap['CATEGORIES'] = '\OCA\Contacts\VObject\GroupProperty';

\OC::$server->getNavigationManager()->add(array(
	'id' => 'contacts',
	'order' => 10,
	'href' => \OCP\Util::linkToRoute('contacts_index'),
	'icon' => \OCP\Util::imagePath( 'contacts', 'contacts.svg' ),
	'name' => \OCP\Util::getL10N('contacts')->t('Contacts')
	)
);

\OCP\Util::connectHook('OC_User', 'post_createUser', '\OCA\Contacts\Hooks', 'userCreated');
\OCP\Util::connectHook('OC_User', 'post_deleteUser', '\OCA\Contacts\Hooks', 'userDeleted');
\OCP\Util::connectHook('OCA\Contacts', 'pre_deleteAddressBook', '\OCA\Contacts\Hooks', 'addressBookDeletion');
\OCP\Util::connectHook('OCA\Contacts', 'pre_deleteContact', '\OCA\Contacts\Hooks', 'contactDeletion');
\OCP\Util::connectHook('OCA\Contacts', 'post_createContact', 'OCA\Contacts\Hooks', 'contactAdded');
\OCP\Util::connectHook('OCA\Contacts', 'post_updateContact', '\OCA\Contacts\Hooks', 'contactUpdated');
\OCP\Util::connectHook('OCA\Contacts', 'scanCategories', '\OCA\Contacts\Hooks', 'scanCategories');
\OCP\Util::connectHook('OCA\Contacts', 'indexProperties', '\OCA\Contacts\Hooks', 'indexProperties');
\OCP\Util::connectHook('OC_Calendar', 'getEvents', 'OCA\Contacts\Hooks', 'getBirthdayEvents');
\OCP\Util::connectHook('OC_Calendar', 'getSources', 'OCA\Contacts\Hooks', 'getCalenderSources');

$url = \OC::$server->getRequest()->server['REQUEST_URI'];

if (preg_match('%index.php/apps/files(/.*)?%', $url)) {
	\OCP\Util::addscript('contacts', 'loader');
}

\OC::$server->getSearch()->registerProvider('OCA\Contacts\Search\Provider', array('apps' => array('contacts')));
\OCP\Share::registerBackend('contact', 'OCA\Contacts\Share\Contact');
\OCP\Share::registerBackend('addressbook', 'OCA\Contacts\Share\Addressbook', 'contact');
//\OCP\App::registerPersonal('contacts','personalsettings');
\OCP\App::registerAdmin('contacts', 'admin');

if (\OCP\User::isLoggedIn()) {
	$cm = \OC::$server->getContactsManager();
	$cm->register(function() use ($cm) {
		$userId = \OC::$server->getUserSession()->getUser()->getUID();
		$app = new App($userId);
		$addressBooks = $app->getAddressBooksForUser();
		foreach ($addressBooks as $addressBook)  {
			if ($addressBook->isActive()) {
				$cm->registerAddressBook($addressBook->getSearchProvider());
			}
		}
	});
}

