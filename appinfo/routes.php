<?php
/**
 * @author Thomas Tanghus
 * Copyright (c) 2013 Thomas Tanghus (thomas@tanghus.net)
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
namespace OCA\Contacts;

//use OCA\AppFramework\App as Main;
use OCA\Contacts\Dispatcher;

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
			$dispatcher = new Dispatcher($params);
			$dispatcher->dispatch('AddressBookController', 'userAddressBooks', $params);
		}
	);

$this->create('contacts_address_book_add', 'addressbook/{backend}/add')
	->post()
	->action(
		function($params) {
			session_write_close();
			$dispatcher = new Dispatcher($params);
			$dispatcher->dispatch('AddressBookController', 'addAddressBook', $params);
		}
	)
	->requirements(array('backend', 'addressbookid'));

$this->create('contacts_address_book', 'addressbook/{backend}/{addressbookid}')
	->get()
	->action(
		function($params) {
			session_write_close();
			$dispatcher = new Dispatcher($params);
			$dispatcher->dispatch('AddressBookController', 'getAddressBook', $params);
		}
	)
	->requirements(array('backend', 'addressbookid'));

$this->create('contacts_address_book_update', 'addressbook/{backend}/{addressbookid}')
	->post()
	->action(
		function($params) {
			session_write_close();
			$dispatcher = new Dispatcher($params);
			$dispatcher->dispatch('AddressBookController', 'updateAddressBook', $params);
		}
	)
	->requirements(array('backend', 'addressbookid'));

$this->create('contacts_address_book_delete', 'addressbook/{backend}/{addressbookid}')
	->delete()
	->action(
		function($params) {
			$dispatcher = new Dispatcher($params);
			session_write_close();
			$dispatcher->dispatch('AddressBookController', 'deleteAddressBook', $params);
		}
	)
	->requirements(array('backend', 'addressbookid'));

$this->create('contacts_address_book_activate', 'addressbook/{backend}/{addressbookid}/activate')
	->post()
	->action(
		function($params) {
			session_write_close();
			$dispatcher = new Dispatcher($params);
			$dispatcher->dispatch('AddressBookController', 'activateAddressBook', $params);
		}
	)
	->requirements(array('backend', 'addressbookid'));

$this->create('contacts_address_book_add_contact', 'addressbook/{backend}/{addressbookid}/contact/add')
	->post()
	->action(
		function($params) {
			session_write_close();
			$dispatcher = new Dispatcher($params);
			$dispatcher->dispatch('AddressBookController', 'addChild', $params);
		}
	)
	->requirements(array('backend', 'addressbookid'));

$this->create('contacts_address_book_delete_contact', 'addressbook/{backend}/{addressbookid}/contact/{contactid}')
	->delete()
	->action(
		function($params) {
			session_write_close();
			$dispatcher = new Dispatcher($params);
			$dispatcher->dispatch('AddressBookController', 'deleteChild', $params);
		}
	)
	->requirements(array('backend', 'addressbookid', 'contactid'));

$this->create('contacts_address_book_delete_contacts', 'addressbook/{backend}/{addressbookid}/deleteContacts')
	->post()
	->action(
		function($params) {
			session_write_close();
			$dispatcher = new Dispatcher($params);
			$dispatcher->dispatch('AddressBookController', 'deleteChildren', $params);
		}
	)
	->requirements(array('backend', 'addressbookid', 'contactid'));

$this->create('contacts_address_book_move_contact', 'addressbook/{backend}/{addressbookid}/contact/{contactid}')
	->post()
	->action(
		function($params) {
			session_write_close();
			$dispatcher = new Dispatcher($params);
			$dispatcher->dispatch('AddressBookController', 'moveChild', $params);
		}
	)
	->requirements(array('backend', 'addressbookid', 'contactid'));

$this->create('contacts_import_upload', 'addressbook/{backend}/{addressbookid}/import/upload')
	->post()
	->action(
		function($params) {
			session_write_close();
			$dispatcher = new Dispatcher($params);
			$dispatcher->dispatch('ImportController', 'upload', $params);
		}
	)
	->requirements(array('backend', 'addressbookid'));

$this->create('contacts_import_prepare', 'addressbook/{backend}/{addressbookid}/import/prepare')
	->post()
	->action(
		function($params) {
			session_write_close();
			$dispatcher = new Dispatcher($params);
			$dispatcher->dispatch('ImportController', 'prepare', $params);
		}
	)
	->requirements(array('backend', 'addressbookid'));

$this->create('contacts_import_start', 'addressbook/{backend}/{addressbookid}/import/start')
	->post()
	->action(
		function($params) {
			session_write_close();
			$dispatcher = new Dispatcher($params);
			$dispatcher->dispatch('ImportController', 'start', $params);
		}
	)
	->requirements(array('backend', 'addressbookid'));

$this->create('contacts_import_status', 'addressbook/{backend}/{addressbookid}/import/status')
	->get()
	->action(
		function($params) {
			session_write_close();
			$dispatcher = new Dispatcher($params);
			$dispatcher->dispatch('ImportController', 'status', $params);
		}
	)
	->requirements(array('backend', 'addressbookid'));

$this->create('contacts_address_book_export', 'addressbook/{backend}/{addressbookid}/export')
	->get()
	->action(
		function($params) {
			session_write_close();
			$dispatcher = new Dispatcher($params);
			$dispatcher->dispatch('ExportController', 'exportAddressBook', $params);
		}
	)
	->requirements(array('backend', 'addressbookid'));

$this->create('contacts_contact_export', 'addressbook/{backend}/{addressbookid}/contact/{contactid}/export')
	->get()
	->action(
		function($params) {
			session_write_close();
			$dispatcher = new Dispatcher($params);
			$dispatcher->dispatch('ExportController', 'exportContact', $params);
		}
	)
	->requirements(array('backend', 'addressbook', 'contactid'));

$this->create('contacts_export_selected', 'exportSelected')
	->get()
	->action(
		function($params) {
			session_write_close();
			$dispatcher = new Dispatcher($params);
			$dispatcher->dispatch('ExportController', 'exportSelected', $params);
		}
	);

$this->create('contacts_contact_photo', 'addressbook/{backend}/{addressbookid}/contact/{contactid}/photo')
	->get()
	->action(
		function($params) {
			session_write_close();
			$dispatcher = new Dispatcher($params);
			$dispatcher->dispatch('ContactPhotoController', 'getPhoto', $params);
		}
	)
	->requirements(array('backend', 'addressbook', 'contactid'));

$this->create('contacts_upload_contact_photo', 'addressbook/{backend}/{addressbookid}/contact/{contactid}/photo')
	->post()
	->action(
		function($params) {
			session_write_close();
			$dispatcher = new Dispatcher($params);
			$dispatcher->dispatch('ContactPhotoController', 'uploadPhoto', $params);
		}
	)
	->requirements(array('backend', 'addressbook', 'contactid'));

$this->create('contacts_cache_contact_photo', 'addressbook/{backend}/{addressbookid}/contact/{contactid}/photo/cacheCurrent')
	->get()
	->action(
		function($params) {
			session_write_close();
			$dispatcher = new Dispatcher($params);
			$dispatcher->dispatch('ContactPhotoController', 'cacheCurrentPhoto', $params);
		}
	)
	->requirements(array('backend', 'addressbook', 'contactid'));

$this->create('contacts_cache_fs_photo', 'addressbook/{backend}/{addressbookid}/contact/{contactid}/photo/cacheFS')
	->get()
	->action(
		function($params) {
			session_write_close();
			$dispatcher = new Dispatcher($params);
			$dispatcher->dispatch('ContactPhotoController', 'cacheFileSystemPhoto', $params);
		}
	)
	->requirements(array('backend', 'addressbook', 'contactid'));

$this->create('contacts_tmp_contact_photo', 'addressbook/{backend}/{addressbookid}/contact/{contactid}/photo/{key}/tmp')
	->get()
	->action(
		function($params) {
			session_write_close();
			$dispatcher = new Dispatcher($params);
			$dispatcher->dispatch('ContactPhotoController', 'getTempPhoto', $params);
		}
	)
	->requirements(array('backend', 'addressbook', 'contactid', 'key'));

$this->create('contacts_crop_contact_photo', 'addressbook/{backend}/{addressbookid}/contact/{contactid}/photo/{key}/crop')
	->post()
	->action(
		function($params) {
			session_write_close();
			$dispatcher = new Dispatcher($params);
			$dispatcher->dispatch('ContactPhotoController', 'cropPhoto', $params);
		}
	)
	->requirements(array('backend', 'addressbook', 'contactid', 'key'));

$this->create('contacts_contact_delete_property', 'addressbook/{backend}/{addressbookid}/contact/{contactid}/property/delete')
	->post()
	->action(
		function($params) {
			session_write_close();
			$dispatcher = new Dispatcher($params);
			$dispatcher->dispatch('ContactController', 'deleteProperty', $params);
		}
	)
	->requirements(array('backend', 'addressbook', 'contactid'));

// Save a single property.
$this->create('contacts_contact_save_property', 'addressbook/{backend}/{addressbookid}/contact/{contactid}')
	->method('PATCH')
	->action(
		function($params) {
			session_write_close();
			$dispatcher = new Dispatcher($params);
			$dispatcher->dispatch('ContactController', 'saveProperty', $params);
		}
	)
	->requirements(array('backend', 'addressbook', 'contactid'));

$this->create('contacts_contact_get', 'addressbook/{backend}/{addressbookid}/contact/{contactid}/')
	->get()
	->action(
		function($params) {
			session_write_close();
			$dispatcher = new Dispatcher($params);
			$dispatcher->dispatch('ContactController', 'getContact', $params);
		}
	)
	->requirements(array('backend', 'addressbook', 'contactid'));

// Save all properties. Used for merging contacts.
$this->create('contacts_contact_save_all', 'addressbook/{backend}/{addressbookid}/contact/{contactid}/save')
	->post()
	->action(
		function($params) {
			session_write_close();
			$dispatcher = new Dispatcher($params);
			$dispatcher->dispatch('ContactController', 'saveContact', $params);
		}
	)
	->requirements(array('backend', 'addressbook', 'contactid'));

$this->create('contacts_categories_list', 'groups/')
	->get()
	->action(
		function($params) {
			session_write_close();
			$dispatcher = new Dispatcher($params);
			$dispatcher->dispatch('GroupController', 'getGroups', $params);
		}
	);

$this->create('contacts_categories_add', 'groups/add')
	->post()
	->action(
		function($params) {
			session_write_close();
			$dispatcher = new Dispatcher($params);
			$dispatcher->dispatch('GroupController', 'addGroup', $params);
		}
	);

$this->create('contacts_categories_delete', 'groups/delete')
	->post()
	->action(
		function($params) {
			session_write_close();
			$dispatcher = new Dispatcher($params);
			$dispatcher->dispatch('GroupController', 'deleteGroup', $params);
		}
	);

$this->create('contacts_categories_rename', 'groups/rename')
	->post()
	->action(
		function($params) {
			session_write_close();
			$dispatcher = new Dispatcher($params);
			$dispatcher->dispatch('GroupController', 'renameGroup', $params);
		}
	);

$this->create('contacts_categories_addto', 'groups/addto/{categoryid}')
	->post()
	->action(
		function($params) {
			session_write_close();
			$dispatcher = new Dispatcher($params);
			$dispatcher->dispatch('GroupController', 'addToGroup', $params);
		}
	);

$this->create('contacts_categories_removefrom', 'groups/removefrom/{categoryid}')
	->post()
	->action(
		function($params) {
			session_write_close();
			$dispatcher = new Dispatcher($params);
			$dispatcher->dispatch('GroupController', 'removeFromGroup', $params);
		}
	)
	->requirements(array('categoryid'));

$this->create('contacts_setpreference', 'preference/set')
	->post()
	->action(
		function($params) {
			session_write_close();
			$dispatcher = new Dispatcher($params);
			$dispatcher->dispatch('SettingsController', 'set', $params);
		}
	);

$this->create('contacts_index_properties', 'indexproperties/{user}/')
	->post()
	->action(
		function($params) {
			session_write_close();
			// TODO: Add BackgroundJob for this.
			\OCP\Util::emitHook('OCA\Contacts', 'indexProperties', array());

			\OCP\Config::setUserValue($params['user'], 'contacts', 'contacts_properties_indexed', 'yes');
			\OCP\JSON::success(array('isIndexed' => true));
		}
	)
	->requirements(array('user'))
	->defaults(array('user' => \OCP\User::getUser()));

