<?php

namespace OCA\Contacts;
use \OCA\AppFramework\Core\API;

require_once __DIR__ . '/../3rdparty/vendor/autoload.php';

//require_once __DIR__ . '/../controller/groupcontroller.php';
\Sabre\VObject\Component\VCard::$componentMap['VCARD']	= '\OCA\Contacts\VObject\VCard';
\Sabre\VObject\Component\VCard::$propertyMap['CATEGORIES'] = 'OCA\Contacts\VObject\GroupProperty';
\Sabre\VObject\Component\VCard::$propertyMap['FN']		= '\OC\VObject\StringProperty';
\Sabre\VObject\Component\VCard::$propertyMap['TITLE']	= '\OC\VObject\StringProperty';
\Sabre\VObject\Component\VCard::$propertyMap['ROLE']	= '\OC\VObject\StringProperty';
\Sabre\VObject\Component\VCard::$propertyMap['NOTE']	= '\OC\VObject\StringProperty';
\Sabre\VObject\Component\VCard::$propertyMap['NICKNAME']	= '\OC\VObject\StringProperty';
\Sabre\VObject\Component\VCard::$propertyMap['EMAIL']	= '\OC\VObject\StringProperty';
\Sabre\VObject\Component\VCard::$propertyMap['TEL']		= '\OC\VObject\StringProperty';
\Sabre\VObject\Component\VCard::$propertyMap['IMPP']	= '\OC\VObject\StringProperty';
\Sabre\VObject\Component\VCard::$propertyMap['URL']		= '\OC\VObject\StringProperty';
\Sabre\VObject\Component\VCard::$propertyMap['N']		= '\OC\VObject\CompoundProperty';
\Sabre\VObject\Component\VCard::$propertyMap['ADR']		= '\OC\VObject\CompoundProperty';
\Sabre\VObject\Component\VCard::$propertyMap['GEO']		= '\OC\VObject\CompoundProperty';

// dont break owncloud when the appframework is not enabled
if(\OCP\App::isEnabled('appframework')) {
	$api = new API('contacts');

	$api->addNavigationEntry(array(
		'id' => 'contacts_index',
		'order' => 10,
		'href' => \OCP\Util::linkToRoute('contacts_index'),
		'icon' => \OCP\Util::imagePath( 'contacts', 'contacts.svg' ),
		'name' => \OCP\Util::getL10N('contacts')->t('Contacts')
		)
	);

	$api->connectHook('OC_User', 'post_createUser', '\OCA\Contacts\Hooks', 'userCreated');
	$api->connectHook('OC_User', 'post_deleteUser', '\OCA\Contacts\Hooks', 'userDeleted');
	$api->connectHook('OCA\Contacts', 'pre_deleteAddressBook', '\OCA\Contacts\Hooks', 'addressBookDeletion');
	$api->connectHook('OCA\Contacts', 'pre_deleteContact', '\OCA\Contacts\Hooks', 'contactDeletion');
	$api->connectHook('OCA\Contacts', 'post_createContact', 'OCA\Contacts\Hooks', 'contactAdded');
	$api->connectHook('OCA\Contacts', 'post_updateContact', '\OCA\Contacts\Hooks', 'contactUpdated');
	$api->connectHook('OCA\Contacts', 'scanCategories', '\OCA\Contacts\Hooks', 'scanCategories');
	$api->connectHook('OCA\Contacts', 'indexProperties', '\OCA\Contacts\Hooks', 'indexProperties');

	\OCP\Util::addscript('contacts', 'loader');

	\OC_Search::registerProvider('OCA\Contacts\SearchProvider');
	//\OCP\Share::registerBackend('contact', 'OCA\Contacts\Share_Backend_Contact');
	\OCP\Share::registerBackend('addressbook', 'OCA\Contacts\Share\Addressbook', 'contact');
	//\OCP\App::registerPersonal('contacts','personalsettings');

	if(\OCP\User::isLoggedIn()) {
		$app = new App($api->getUserId());
		$addressBooks = $app->getAddressBooksForUser();
		foreach($addressBooks as $addressBook)  {
			if($addressBook->getBackend()->name === 'local') {
				\OCP\Contacts::registerAddressBook(new AddressbookProvider($addressBook));
			}
		}
	}
} else {
	\OCP\Util::writeLog('contacts', 'AppFramework is not enabled. App is not functional!', \OCP\Util::ERROR);
}
