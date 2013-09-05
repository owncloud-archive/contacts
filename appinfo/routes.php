<?php
/**
 * @author Thomas Tanghus
 * Copyright (c) 2013 Thomas Tanghus (thomas@tanghus.net)
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
namespace OCA\Contacts;

use OCA\AppFramework\App as Main;
use OCA\Contacts\DIContainer;

//define the routes
//for the index
$this->create('contacts_index', '/')
	->actionInclude('contacts/index.php');
// 	->action(
// 		function($params){
// 			//
// 		}
// 	);

$this->create('contacts_jsconfig', 'ajax/config.js')
	->actionInclude('contacts/js/config.php');

/* TODO: Check what it requires to be a RESTful API. I think maybe {user}
	shouldn't be in the URI but be authenticated in headers or elsewhere.
*/
$this->create('contacts_address_books_for_user', 'addressbooks/')
	->get()
	->action(
		function($params) {
			session_write_close();
			Main::main('AddressBookController', 'userAddressBooks', $params, new DIContainer());
		}
	);

$this->create('contacts_address_book_add', 'addressbook/{backend}/add')
	->post()
	->action(
		function($params) {
			session_write_close();
			Main::main('AddressBookController', 'addAddressBook', $params, new DIContainer());
		}
	)
	->requirements(array('backend', 'addressbookid'));

$this->create('contacts_address_book', 'addressbook/{backend}/{addressbookid}')
	->get()
	->action(
		function($params) {
			session_write_close();
			Main::main('AddressBookController', 'getAddressBook', $params, new DIContainer());
		}
	)
	->requirements(array('backend', 'addressbookid'));

$this->create('contacts_address_book_export', 'addressbook/{backend}/{addressbookid}/export')
	->get()
	->action(
		function($params) {
			session_write_close();
			Main::main('AddressBookController', 'exportAddressBook', $params, new DIContainer());
		}
	)
	->requirements(array('backend', 'addressbookid'));

$this->create('contacts_address_book_update', 'addressbook/{backend}/{addressbookid}')
	->post()
	->action(
		function($params) {
			session_write_close();
			Main::main('AddressBookController', 'updateAddressBook', $params, new DIContainer());
		}
	)
	->requirements(array('backend', 'addressbookid'));

$this->create('contacts_address_book_delete', 'addressbook/{backend}/{addressbookid}')
	->delete()
	->action(
		function($params) {
			session_write_close();
			Main::main('AddressBookController', 'deleteAddressBook', $params, new DIContainer());
		}
	)
	->requirements(array('backend', 'addressbookid'));

$this->create('contacts_address_book_activate', 'addressbook/{backend}/{addressbookid}/activate')
	->post()
	->action(
		function($params) {
			session_write_close();
			Main::main('AddressBookController', 'activateAddressBook', $params, new DIContainer());
		}
	)
	->requirements(array('backend', 'addressbookid'));

$this->create('contacts_address_book_add_contact', 'addressbook/{backend}/{addressbookid}/contact/add')
	->post()
	->action(
		function($params) {
			session_write_close();
			Main::main('AddressBookController', 'addChild', $params, new DIContainer());
		}
	)
	->requirements(array('backend', 'addressbookid'));

$this->create('contacts_address_book_delete_contact', 'addressbook/{backend}/{addressbookid}/contact/{contactid}')
	->delete()
	->action(
		function($params) {
			session_write_close();
			Main::main('AddressBookController', 'deleteChild', $params, new DIContainer());
		}
	)
	->requirements(array('backend', 'addressbookid', 'contactid'));

$this->create('contacts_address_book_delete_contacts', 'addressbook/{backend}/{addressbookid}/deleteContacts')
	->post()
	->action(
		function($params) {
			session_write_close();
			Main::main('AddressBookController', 'deleteChildren', $params, new DIContainer());
		}
	)
	->requirements(array('backend', 'addressbookid', 'contactid'));

$this->create('contacts_address_book_move_contact', 'addressbook/{backend}/{addressbookid}/contact/{contactid}')
	->post()
	->action(
		function($params) {
			session_write_close();
			Main::main('AddressBookController', 'moveChild', $params, new DIContainer());
		}
	)
	->requirements(array('backend', 'addressbookid', 'contactid'));

$this->create('contacts_import_upload', 'addressbook/{backend}/{addressbookid}/import/upload')
	->post()
	->action(
		function($params) {
			session_write_close();
			Main::main('ImportController', 'upload', $params, new DIContainer());
		}
	)
	->requirements(array('backend', 'addressbookid'));

$this->create('contacts_import_start', 'addressbook/{backend}/{addressbookid}/import/start')
	->post()
	->action(
		function($params) {
			session_write_close();
			Main::main('ImportController', 'start', $params, new DIContainer());
		}
	)
	->requirements(array('backend', 'addressbookid'));

$this->create('contacts_import_status', 'addressbook/{backend}/{addressbookid}/import/status')
	->get()
	->action(
		function($params) {
			session_write_close();
			Main::main('ImportController', 'status', $params, new DIContainer());
		}
	)
	->requirements(array('backend', 'addressbookid'));

$this->create('contacts_contact_photo', 'addressbook/{backend}/{addressbookid}/contact/{contactid}/photo')
	->get()
	->action(
		function($params) {
			session_write_close();
			Main::main('ContactPhotoController', 'getPhoto', $params, new DIContainer());
		}
	)
	->requirements(array('backend', 'addressbook', 'contactid'));

$this->create('contacts_upload_contact_photo', 'addressbook/{backend}/{addressbookid}/contact/{contactid}/photo')
	->post()
	->action(
		function($params) {
			session_write_close();
			Main::main('ContactPhotoController', 'uploadPhoto', $params, new DIContainer());
		}
	)
	->requirements(array('backend', 'addressbook', 'contactid'));

$this->create('contacts_cache_contact_photo', 'addressbook/{backend}/{addressbookid}/contact/{contactid}/photo/cacheCurrent')
	->get()
	->action(
		function($params) {
			session_write_close();
			Main::main('ContactPhotoController', 'cacheCurrentPhoto', $params, new DIContainer());
		}
	)
	->requirements(array('backend', 'addressbook', 'contactid'));

$this->create('contacts_cache_fs_photo', 'addressbook/{backend}/{addressbookid}/contact/{contactid}/photo/cacheFS')
	->get()
	->action(
		function($params) {
			session_write_close();
			Main::main('ContactPhotoController', 'cacheFileSystemPhoto', $params, new DIContainer());
		}
	)
	->requirements(array('backend', 'addressbook', 'contactid'));

$this->create('contacts_tmp_contact_photo', 'addressbook/{backend}/{addressbookid}/contact/{contactid}/photo/{key}/tmp')
	->get()
	->action(
		function($params) {
			session_write_close();
			Main::main('ContactPhotoController', 'getTempPhoto', $params, new DIContainer());
		}
	)
	->requirements(array('backend', 'addressbook', 'contactid', 'key'));

$this->create('contacts_crop_contact_photo', 'addressbook/{backend}/{addressbookid}/contact/{contactid}/photo/{key}/crop')
	->post()
	->action(
		function($params) {
			session_write_close();
			Main::main('ContactPhotoController', 'cropPhoto', $params, new DIContainer());
		}
	)
	->requirements(array('backend', 'addressbook', 'contactid', 'key'));

$this->create('contacts_contact_export', 'addressbook/{backend}/{addressbookid}/contact/{contactid}/export')
	->get()
	->action(
		function($params) {
			session_write_close();
			Main::main('ContactController', 'exportContact', $params, new DIContainer());
		}
	)
	->requirements(array('backend', 'addressbook', 'contactid'));

$this->create('contacts_contact_delete_property', 'addressbook/{backend}/{addressbookid}/contact/{contactid}/property/delete')
	->post()
	->action(
		function($params) {
			session_write_close();
			Main::main('ContactController', 'deleteProperty', $params, new DIContainer());
		}
	)
	->requirements(array('backend', 'addressbook', 'contactid'));

// Save a single property.
$this->create('contacts_contact_save_property', 'addressbook/{backend}/{addressbookid}/contact/{contactid}/property/save')
	->post()
	->action(
		function($params) {
			session_write_close();
			Main::main('ContactController', 'saveProperty', $params, new DIContainer());
		}
	)
	->requirements(array('backend', 'addressbook', 'contactid'));

$this->create('contacts_contact_get', 'addressbook/{backend}/{addressbookid}/contact/{contactid}/')
	->get()
	->action(
		function($params) {
			session_write_close();
			Main::main('ContactController', 'getContact', $params, new DIContainer());
		}
	)
	->requirements(array('backend', 'addressbook', 'contactid'));

// Save all properties. Used for merging contacts.
$this->create('contacts_contact_save_all', 'addressbook/{backend}/{addressbookid}/contact/{contactid}/save')
	->post()
	->action(
		function($params) {
			session_write_close();
			Main::main('ContactController', 'saveContact', $params, new DIContainer());
		}
	)
	->requirements(array('backend', 'addressbook', 'contactid'));

$this->create('contacts_categories_list', 'groups/')
	->get()
	->action(
		function($params) {
			session_write_close();
			Main::main('GroupController', 'getGroups', $params, new DIContainer());
		}
	);

$this->create('contacts_categories_add', 'groups/add')
	->post()
	->action(
		function($params) {
			session_write_close();
			Main::main('GroupController', 'addGroup', $params, new DIContainer());
		}
	);

$this->create('contacts_categories_delete', 'groups/delete')
	->post()
	->action(
		function($params) {
			session_write_close();
			Main::main('GroupController', 'deleteGroup', $params, new DIContainer());
		}
	);

$this->create('contacts_categories_rename', 'groups/rename')
	->post()
	->action(
		function($params) {
			session_write_close();
			Main::main('GroupController', 'renameGroup', $params, new DIContainer());
		}
	);

$this->create('contacts_categories_addto', 'groups/addto/{categoryid}')
	->post()
	->action(
		function($params) {
			session_write_close();
			Main::main('GroupController', 'addToGroup', $params, new DIContainer());
		}
	);

$this->create('contacts_categories_removefrom', 'groups/removefrom/{categoryid}')
	->post()
	->action(
		function($params) {
			session_write_close();
			Main::main('GroupController', 'removeFromGroup', $params, new DIContainer());
		}
	)
	->requirements(array('categoryid'));

$this->create('contacts_setpreference', 'preference/set')
	->post()
	->action(
		function($params) {
			session_write_close();
			Main::main('SettingsController', 'set', $params, new DIContainer());
		}
	);

$this->create('contacts_index_properties', 'indexproperties/{user}/')
	->post()
	->action(
		function($params) {
			session_write_close();
			// TODO: Add BackgroundJob for this.
			\OC_Hook::emit('OCA\Contacts', 'indexProperties', array());

			\OCP\Config::setUserValue($params['user'], 'contacts', 'contacts_properties_indexed', 'yes');
			\OCP\JSON::success(array('isIndexed' => true));
		}
	)
	->requirements(array('user'))
	->defaults(array('user' => \OCP\User::getUser()));

