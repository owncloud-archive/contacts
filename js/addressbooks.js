OC.Contacts = OC.Contacts || {};


(function(window, $, OC) {
	'use strict';

	var AddressBook = function(storage, book, template, isFileAction) {
		this.isFileAction = isFileAction || false;
		this.storage = storage;
		this.book = book;
		this.$template = template;
		this.addressBooks = new OC.Contacts.AddressBookList(
			this.storage,
			$('#app-settings-content'),
			$('#addressBookTemplate')
		);
	};

	AddressBook.prototype.render = function() {
		var self = this;
		//var dialog = OC.Contacts.Dialog(null, null);
		
		this.$li = this.$template.octemplate({
			id: this.book.id,
			displayname: this.book.displayname,
			backend: this.book.backend,
			permissions: this.book.permissions
		});
		if (this.isFileAction) {
			return this.$li;
		}
		this.$li.find('a.action').tipsy({gravity: 'w'});
		if (!this.hasPermission(OC.PERMISSION_DELETE)) {
			this.$li.find('a.action.delete').hide();
		}
		if (!this.hasPermission(OC.PERMISSION_UPDATE)) {
			this.$li.find('a.action.edit').hide();
		}
		if (!this.hasPermission(OC.PERMISSION_SHARE)) {
			this.$li.find('a.action.share').hide();
		}
		if (['local', 'ldap', 'shared'].indexOf(this.getBackend()) === -1) {
			this.$li.find('a.action.carddav').hide();
		}
		this.$li.find('input:checkbox').prop('checked', this.book.active).on('change', function() {
			console.log('activate', self.getId());
			var checkbox = $(this).get(0);
			self.setActive(checkbox.checked, function(response) {
				if(!response.error) {
					self.book.active = checkbox.checked;
				} else {
					checkbox.checked = !checkbox.checked;
				}
			});
		});
		this.$li.find('a.action.download')
			.attr('href', OC.generateUrl(
				'apps/contacts/addressbook/{backend}/{addressBookId}/export',
				{
					backend: this.getBackend(),
					addressBookId: this.getId()
				}
			));
		this.$li.find('a.action.delete').on('click keypress', function() {
			$('.tipsy').remove();
			console.log('delete', self.getId());
			self.destroy();
		});
		this.$li.find('a.action.carddav').on('click keypress', function() {
			var uri = (self.book.owner === oc_current_user ) ? self.book.uri : self.book.uri + '_shared_by_' + self.book.owner;
			var link = OC.linkToRemote('carddav')+'/addressbooks/'+encodeURIComponent(oc_current_user)+'/'+encodeURIComponent(uri);
			var $dropdown = $('<li><div id="dropdown" class="drop"><input type="text" value="{link}" readonly /></div></li>')
				.octemplate({link:link}).insertAfter(self.$li);
			var $input = $dropdown.find('input');
			$input.focus().get(0).select();
			$input.on('blur', function() {
				$dropdown.hide('blind', function() {
					$dropdown.remove();
				});
			});
		});
		$('#add-ldap-address-book-element').on('click keypress', function() {
			var $rightContent = $('#app-content');
			$rightContent.append('<div id="addressbook-ui-dialog"></div>');
			var $dlg = $('#addressBookConfigTemplate').octemplate({backend: 'ldap'});
			var $divDlg = $('#addressbook-ui-dialog');
			$divDlg.html($dlg).ocdialog({
				modal: true,
				closeOnEscape: true,
				title: t('contacts', 'Add new LDAP Addressbook'),
				height: 'auto',
				width: 'auto',
				buttons: [
					{
						text: t('contacts', 'Ok'),
						click: function() {
							OC.Contacts.otherBackendConfig.addressbookUiOk($divDlg);
						},
						defaultButton: true
					},
					{
						text: t('contacts', 'Cancel'),
						click: function() {
							OC.Contacts.otherBackendConfig.addressbookUiClose($divDlg);
						}
					}
				],
				close: function(/*event, ui*/) {
					OC.Contacts.otherBackendConfig.addressbookUiClose($divDlg);
				},
				open: function(/*event, ui*/) {
					OC.Contacts.otherBackendConfig.openAddressbookUi();
				}
			});
		});
		this.$li.find('a.action.edit').on('click keypress', function(event) {
			$.when(self.storage.getAddressBook(self.getBackend(), self.getId()))
			.then(function(response) {
			if(!response.error) {
				if(response.data) {
					var addressbook = response.data;
					console.log('addressbook', addressbook);
					if (addressbook.backend === 'local') {
						if($(this).data('open')) {
							return;
						}
						var editor = this;
						event.stopPropagation();
						event.preventDefault();
						var $dropdown = $('<li><div><input type="text" value="{name}" /></div></li>')
							.octemplate({name:self.getDisplayName()}).insertAfter(self.$li);
						var $input = $dropdown.find('input');
						//$input.focus().get(0).select();
						$input.addnew({
							autoOpen: true,
							//autoClose: false,
							addText: t('contacts', 'Save'),
							ok: function(event, name) {
								console.log('edit-address-book ok', name);
								$input.addClass('loading');
								self.update({displayname:name}, function(response) {
									console.log('response', response);
									if(response.error) {
										$(document).trigger('status.contacts.error', response);
									} else {
										self.setDisplayName(response.data.displayname);
										$input.addnew('close');
									}
									$input.removeClass('loading');
								});
							},
							close: function() {
								$dropdown.remove();
								$(editor).data('open', false);
							}
						});
						$(this).data('open', true);
					} else {
						var $rightContent = $('#app-content');
						$rightContent.append('<div id="addressbook-ui-dialog"></div>');
						var $dlg = $('#addressBookConfigTemplate').octemplate({backend: 'ldap'});
						var $divDlg = $('#addressbook-ui-dialog');
						//var $divDlg = $('#addressbook-ui-dialog');
						$divDlg.html($dlg).ocdialog({
							modal: true,
							closeOnEscape: true,
							title: t('contacts', 'Edit Addressbook'),
							height: 'auto', width: 'auto',
							buttons: [
								{
									text: t('contacts', 'Ok'),
									click: function() {
										OC.Contacts.otherBackendConfig.addressbookUiEditOk($divDlg);
										self.setDisplayName($('#addressbooks-ui-name').val());
									},
									defaultButton: true
								},
								{
									text: t('contacts', 'Cancel'),
									click: function() {
										OC.Contacts.otherBackendConfig.addressbookUiClose($divDlg);
									}
								}
							],
							close: function() {
								OC.Contacts.otherBackendConfig.addressbookUiClose($divDlg);
							},
							open: function() {
								OC.Contacts.otherBackendConfig.editAddressbookUI(addressbook);
							}
						});
					}
					return this.$li;
				}
			} else {
				console.warn('Addressbook getAddressbook - no data !!');
			}
			})
			.fail(function(response) {
				console.warn('Request Failed:', response.message);
				$(document).trigger('status.contacts.error', response);
			});
		});
		return this.$li;
	};

	AddressBook.prototype.getId = function() {
		return String(this.book.id);
	};

	AddressBook.prototype.getBackend = function() {
		return this.book.backend;
	};

	AddressBook.prototype.getDisplayName = function() {
		return this.book.displayname;
	};

	AddressBook.prototype.setDisplayName = function(name) {
		this.book.displayname = name;
		this.$li.find('label').text(escapeHTML(name));
	};

	AddressBook.prototype.getPermissions = function() {
		return this.book.permissions;
	};

	AddressBook.prototype.hasPermission = function(permission) {
		return (this.getPermissions() & permission);
	};

	AddressBook.prototype.getOwner = function() {
		return this.book.owner;
	};

	AddressBook.prototype.getMetaData = function() {
		return {
			permissions:this.getPermissions,
			backend: this.getBackend(),
			id: this.getId(),
			displayname: this.getDisplayName()
		};
	};

	/**
	 * Update address book in data store
	 * @param object properties An object current only supporting the property 'displayname'
	 * @param cb Optional callback function which
	 * @return An object with a boolean variable 'error'.
	 */
	AddressBook.prototype.update = function(properties, cb) {
		return $.when(this.storage.updateAddressBook(this.getBackend(), this.getId(), {properties:properties}))
			.then(function(response) {
			if(response.error) {
				$(document).trigger('status.contacts.error', response);
			}
			cb(response);
		});
	};

	AddressBook.prototype.isActive = function() {
		return this.book.active;
	};

	/**
	 * Save an address books active state to data store.
	 * @param bool state
	 * @param cb Optional callback function which
	 * @return An object with a boolean variable 'error'.
	 */
	AddressBook.prototype.setActive = function(state, cb) {
		var self = this;
		return $.when(this.storage.activateAddressBook(this.getBackend(), this.getId(), state))
			.then(function(response) {
			if(response.error) {
				$(document).trigger('status.contacts.error', response);
			} else {
				$(document).trigger('status.addressbook.activated', {
					addressbook: self,
					state: state
				});
			}
			cb(response);
		});
	};

	/**
	 * Delete a list of contacts from the data store
	 * @param array contactsIds An array of contact ids to be deleted.
	 * @param cb Optional callback function which will be passed:
	 * @return An object with a boolean variable 'error'.
	 */
	AddressBook.prototype.deleteContacts = function(contactsIds, cb) {
		console.log('deleteContacts', contactsIds);
		return $.when(this.storage.deleteContacts(this.getBackend(), this.getId(), contactsIds))
			.then(function(response) {
			if(response.error) {
				$(document).trigger('status.contacts.error', response);
			}
			if(typeof cb === 'function') {
				cb(response);
			}
		});
	};

	/**
	 * Delete address book from data store and remove it from the DOM
	 * @return An object with a boolean variable 'error'.
	 */
	AddressBook.prototype.destroy = function() {
		var self = this;
		$.when(this.storage.deleteAddressBook(this.getBackend(), self.getId()))
			.then(function(response) {
			if(!response.error) {
				self.$li.remove();
				$(document).trigger('status.addressbook.removed', {
					addressbook: self
				});
			} else {
				$(document).trigger('status.contacts.error', response);
			}
		}).fail(function(response) {
			console.log(response.message);
			$(document).trigger('status.contacts.error', response);
		});
	};

	/**
	 * Controls access to address books
	 */
	var AddressBookList = function(
			storage,
			bookTemplate,
			bookItemTemplate,
			isFileAction
		) {
		var self = this;
		this.isFileAction = isFileAction || false;
		this.storage = storage;
		this.$bookTemplate = bookTemplate;
		this.$bookList = this.$bookTemplate.find('.addressbooklist');
		this.$bookItemTemplate = bookItemTemplate;
		this.$importIntoSelect = $('#contacts-import-into');
		this.$importFormatSelect = $('#contacts-import-format');
		this.$importProgress = $('#import-status-progress');
		this.$importStatusText = $('#import-status-text');
		this.addressBooks = [];

		if(this.isFileAction) {
			return;
		}
		this.$importFileInput = $('#contacts-import-upload-start');
		var $addInput = this.$bookTemplate.find('#add-address-book');
		$addInput.addnew({
			ok: function(event, name) {
				console.log('add-address-book ok', name);
				$addInput.addClass('loading');
				self.add(name, function(response) {
					console.log('response', response);
					if(response.error) {
						$(document).trigger('status.contacts.error', response);
					} else {
						$(this).addnew('close');
					}
					$addInput.removeClass('loading');
				});
			}
		});

		$(document).bind('status.addressbook.removed', function(e, data) {
			var addressBook = data.addressbook;
			self.addressBooks.splice(self.addressBooks.indexOf(addressBook), 1);
		});
		$('#oc-import-nocontact').unbind('click').click(function(event) {
			console.log('triggered', event);
			self.importDialog();
		});
		$('#import-contacts').unbind('click').click(function() {
			console.log('Import clicked');
			self.importDialog();
		});
	};
	
	AddressBookList.prototype.importDialog = function() {
		var $parent = $('body');
		$parent.append('<div id="import-dialog"></div>');
		var $dlg = $('#contactsImportTemplate').clone().octemplate();
		var $divDlg = $('#import-dialog');
		var self = this;
		$divDlg.html($dlg).ocdialog({
			modal: true,
			closeOnEscape: true,
			title: t('contacts', 'Import contacts'),
			height: 'auto',
			width: 'auto',
			buttons: [
				{
					text: t('contacts', 'Upload file...'),
					click: function() {
						$('#contacts-import-upload-start').click();
					}
				}
			],
			close: function(/*event, ui*/) {
				$('#import-dialog').ocdialog('close').ocdialog('destroy').remove();
			},
			open: function(/*event, ui*/) {
				self.openImportDialog();
			}
		});
	};
	
	AddressBookList.prototype.openImportDialog = function() {
		this.$importIntoSelect = $('#contacts-import-into');
		this.$importFormatSelect = $('#contacts-import-format');
		this.$importProgress = $('#import-status-progress');
		this.$importStatusText = $('#import-status-text');
		this.$importFileInput = $('#contacts-import-upload-start');
		var me = this;
		var self = this;
		this.$importFileInput.fileupload({
			dataType: 'json',
			start: function(e, data) {
				me.$importProgress.progressbar({value:false});
				$('.tipsy').remove();
				$('.import-status').show();
				me.$importProgress.fadeIn();
				me.$importStatusText.text(t('contacts', 'Starting file import'));
				$('.oc-dialog-buttonrow button, #contacts-import-into-p, #contacts-import-format-p').hide();
			},
			done: function (e, data) {
				if (me.$importFormatSelect.find('option:selected').val() != 'automatic') {
					me.$importStatusText.text(t('contacts', 'Format selected: {format}',
													{format: $('#contacts-import-format').find('option:selected').text() }));
				} else {
					me.$importStatusText.text(t('contacts', 'Automatic format detection'));
				}
				console.log('Upload done:', self.addressBooks);
				self.doImport(self.storage.formatResponse(data.jqXHR));
			},
			fail: function(e, data) {
				console.log('fail', data);
				OC.notify({message:data.errorThrown + ': ' + data.textStatus});
				$('.import-status').hide();
				$('.oc-dialog-buttonrow button, #contacts-import-into-p, #contacts-import-format-p').show();
			}
		});
		var $import_into = $('#contacts-import-into');
		$import_into.change(function() {
			if ($(this).val() !== '-1') {
				var url = OC.generateUrl(
					'apps/contacts/addressbook/{backend}/{addressBookId}/{importType}/import/upload',
					{
						addressBookId:$import_into.val(),
						importType:me.$importFormatSelect.find('option:selected').val(),
						backend: $import_into.find('option:selected').attr('backend')
					}
				);
				me.$importFileInput.fileupload().fileupload('option', 'url', url);
				me.$importFileInput.attr('disabled', false);
			} else {
				me.$importFileInput.attr('disabled', true);
			}
		});
		$.when(self.storage.getAddressBooksForUser()).then(function(response) {
			if(!response.error) {
				$import_into.empty();
				var $option = $('<option value="-1">' + t('contacts', 'Import into...') + '</option>');
				$import_into.append($option);
				var nbOptions = 0;
				$.each(response.data.addressbooks, function(idx, addressBook) {
					if (addressBook.permissions & OC.PERMISSION_UPDATE) {
						var $option=$('<option></option>').val(addressBook.id).html(addressBook.displayname).attr('backend', addressBook.backend);
						self.insertAddressBookWithoutRender(addressBook);
						$import_into.append($option);
						nbOptions++;
					}
				});
				if (nbOptions === 1) {
					$import_into.val($import_into.find('option:not([value="-1"])').first().val());
					$import_into.attr('disabled', true);
					me.$importFileInput.attr('disabled', false);
					var url = OC.generateUrl(
						'apps/contacts/addressbook/{backend}/{addressBookId}/{importType}/import/upload',
						{
							addressBookId:$import_into.val(),
							importType:me.$importFormatSelect.find('option:selected').val(),
							backend: $import_into.find('option:selected').attr('backend')
						}
					);
					me.$importFileInput.fileupload('option', 'url', url);
				}
			} else {
				console.log('status.contacts.error', response);
			}
		});
	};

	AddressBookList.prototype.count = function() {
		return this.addressBooks.length;
	};

	/**
	 * For importing from oC filesystem
	 */
	AddressBookList.prototype.prepareImport = function(backend, addressBookId, importType, path, fileName) {
		console.log('prepareImport', backend, addressBookId, importType, path, fileName);
		this.$importStatusText = $('#import-status-text');
		this.$importProgress = $('#import-status-progress');
		this.$importProgress.progressbar({value:false});
		if (importType != 'automatic') {
			this.$importStatusText.text(t('contacts', 'Format selected: {format}',
											{format: self.$importFormatSelect.find('option:selected').val() }));
		} else {
			this.$importStatusText.text(t('contacts', 'Automatic format detection'));
		}
		return this.storage.prepareImport(
				backend, addressBookId, importType,
				{filename:fileName, path:path}
			);
	};

	AddressBookList.prototype.doImport = function(response) {
		console.log('doImport', response);
		this.$importProgress = $('#import-status-progress');
		this.$importStatusText = $('#import-status-text');
		var defer = $.Deferred();
		var done = false;
		var interval = null, isChecking = false;
		var self = this;
		var closeImport = function() {
			defer.resolve();
		};
		if(!response.error) {
			this.$importProgress.progressbar('value', 0);
			var data = response.data;
			var getStatus = function(backend, addressbookid, importType, progresskey, interval, done) {
				if(done) {
					clearInterval(interval);
					closeImport();
					return;
				}
				if(isChecking) {
					return;
				}
				isChecking = true;
				$.when(
					self.storage.importStatus(
						backend, addressbookid, importType,
						{progresskey:progresskey}
					))
				.then(function(response) {
					if(!response.error) {
						console.log('status, response: ', response);
						if (response.data.total != null && response.data.progress != null) {
							console.log('response.data', response.data);
							self.$importProgress.progressbar('option', 'max', Number(response.data.total));
							self.$importProgress.progressbar('value', Number(response.data.progress));
							self.$importStatusText.text(t('contacts', 'Processing {count}/{total} cards',
														{count: response.data.progress, total: response.data.total}));
						}
					} else {
						console.warn('Error', response.message);
						self.$importStatusText.text(response.message);
					}
					isChecking = false;
				}).fail(function(response) {
					console.log(response.message);
					$(document).trigger('status.contacts.error', response);
					isChecking = false;
				});
			};
			console.log('vertige', data);
			$.when(
				self.storage.startImport(
					/* this is a hack for a workaround on a bug. (described in contacts/issues/#488 for example)
 					 * instead of using data.backend, data.addressBookId and data.importType, I use
 					 * this.$importIntoSelect.find('option:selected').data('backend'),
 					 * this.$importIntoSelect.val() and this.$importFormatSelect.val()
 					 * This is because for some unknown reason yet, data isn't updated with select data after an import has been made
	 				 * so every import was made on the same addressbook with the same import type
 					 * I don't understand yet why the data object isn't updated after an upload, so I put this patch which works
 					 * but is ugly as hell
 					 */
					this.$importIntoSelect.find('option:selected').attr('backend'), this.$importIntoSelect.val(), this.$importFormatSelect.val(),
					{filename:data.filename, progresskey:data.progresskey}
				)
			)
			.then(function(response) {
				console.log('response', response);
				if(!response.error) {
					console.log('Import done');
					$('#contacts-import-upload').hide();
					self.$importStatusText.text(t('contacts', 'Total: {total}, Success: {imported}, Errors: {failed}',
													  {total: response.data.total, imported:response.data.imported, failed: response.data.failed}));
					self.$importProgress.progressbar('option', 'max', response.data.total);
					self.$importProgress.progressbar('value', response.data.total);
					var addressBook = self.find({id:data.addressBookId, backend: data.backend});
					console.log('addressBook', self.count(), self.addressBooks);
					$(document).trigger('status.addressbook.imported', {
						addressbook: addressBook
					});
					defer.resolve();
				} else {
					defer.reject(response);
					self.$importStatusText.text(response.message);
					$(document).trigger('status.contacts.error', response);
				}
				done = true;
			}).fail(function(response) {
				defer.reject(response);
				console.log(response.message);
				$(document).trigger('status.contacts.error', response);
				done = true;
			});
		} else {
			defer.reject(response);
			done = true;
			self.$importStatusText.text(response.message);
			closeImport();
			$(document).trigger('status.contacts.error', response);
		}
		return defer;
	};

	/**
	 * Create an AddressBook object, save it in internal list and append it's rendered result to the list
	 *
	 * @param object addressBook
	 * @param bool rendered If true add the addressbook to the addressbook list
	 * @return AddressBook
	 */
	AddressBookList.prototype.insertAddressBook = function(addressBook) {
		var book = new AddressBook(this.storage, addressBook, this.$bookItemTemplate, this.isFileAction);
		if(!this.isFileAction) {
			var result = book.render();
			this.$bookList.append(result);
		}
		this.addressBooks.push(book);
		return book;
	};
	
	/**
	 * Create an AddressBook object, save it in internal list and append it's rendered result to the list
	 *
	 * @param object addressBook
	 * @param bool rendered If true add the addressbook to the addressbook list
	 * @return AddressBook
	 */
	AddressBookList.prototype.insertAddressBookWithoutRender = function(addressBook) {
		var book = new AddressBook(this.storage, addressBook, this.$bookItemTemplate, this.isFileAction);
		this.addressBooks.push(book);
		return book;
	};
	
	/**
	 * Get an AddressBook
	 *
	 * @param object info An object with the string  properties 'id' and 'backend'
	 * @return AddressBook|null
	 */
	AddressBookList.prototype.find = function(info) {
		console.log('AddressBookList.find', info);
		var addressBook = null;
		$.each(this.addressBooks, function(idx, book) {
			if(book.getId() === String(info.id) && book.getBackend() === info.backend) {
				addressBook = book;
				return false; // break loop
			}
		});
		return addressBook;
	};

	/**
	 * Move a contacts from one address book to another..
	 *
	 * @param Contact The contact to move
	 * @param object from An object with properties 'id' and 'backend'.
	 * @param object target An object with properties 'id' and 'backend'.
	 */
	AddressBookList.prototype.moveContact = function(contact, from, target) {
		console.log('AddressBookList.moveContact, contact', contact, from, target);
		$.when(this.storage.moveContact(from.backend, from.id, contact.getId(), {target:target}))
			.then(function(response) {
			if(!response.error) {
				console.log('Contact moved', response);
				$(document).trigger('status.contact.moved', {
					contact: contact,
					data: response.data
				});
			} else {
				$(document).trigger('status.contacts.error', response);
			}
		});
	};

	/**
	 * Get an array of address books with at least the required permission.
	 *
	 * @param int permission
	 * @param bool noClone If true the original objects will be returned and can be manipulated.
	 */
	AddressBookList.prototype.selectByPermission = function(permission, noClone) {
		var books = [];
		$.each(this.addressBooks, function(idx, book) {
			if(book.getPermissions() & permission) {
				// Clone the address book not to mess with with original
				books.push(noClone ? book : $.extend(true, {}, book));
			}
		});
		return books;
	};

	/**
	 * Add a new address book.
	 *
	 * @param string name
	 * @param function cb
	 * @return jQuery.Deferred
	 * @throws Error
	 */
	AddressBookList.prototype.add = function(name, cb) {
		console.log('AddressBookList.add', name, typeof cb);
		var defer = $.Deferred();
		// Check for wrong, duplicate or empty name
		if(typeof name !== 'string') {
			throw new TypeError('BadArgument: AddressBookList.add() only takes String arguments.');
		}
		if(name.trim() === '') {
			throw new Error('BadArgument: Cannot add an address book with an empty name.');
		}
		var error = '';
		$.each(this.addressBooks, function(idx, book) {
			if(book.getDisplayName() === name) {
				console.log('Dupe');
				error = t('contacts', 'An address book called {name} already exists', {name:name});
				if(typeof cb === 'function') {
					cb({error:true, message:error});
				}
				defer.reject({error:true, message:error});
				return false; // break loop
			}
		});
		if(error.length) {
			console.warn('Error:', error);
			return defer;
		}
		var self = this;
		$.when(this.storage.addAddressBook('local',
		{displayname: name, description: ''})).then(function(response) {
			if(response.error) {
				error = response.message;
				if(typeof cb === 'function') {
					cb({error:true, message:error});
				}
				defer.reject(response);
			} else {
				var book = self.insertAddressBook(response.data);
				$(document).trigger('status.addressbook.added');
				if(typeof cb === 'function') {
					cb({error:false, addressbook: book});
				}
				defer.resolve({error:false, addressbook: book});
			}
		})
		.fail(function(jqxhr, textStatus, error) {
			$(this).removeClass('loading');
			var err = textStatus + ', ' + error;
			console.log('Request Failed', + err);
			error = t('contacts', 'Failed adding address book: {error}', {error:err});
			if(typeof cb === 'function') {
				cb({error:true, message:error});
			}
			defer.reject({error:true, message:error});
		});
		return defer;
	};

	/**
	* Load address books
	*/
	AddressBookList.prototype.loadAddressBooks = function() {
		var self = this;
		var defer = $.Deferred();
		$.when(this.storage.getAddressBooksForUser()).then(function(response) {
			if(!response.error) {
				$.each(response.data.addressbooks, function(idx, addressBook) {
					self.insertAddressBook(addressBook);
				});
				if(!self.isFileAction) {
					if(typeof OC.Share !== 'undefined') {
						OC.Share.loadIcons('addressbook');
					} else {
						self.$bookList.find('a.action.share').css('display', 'none');
					}
				}
				console.log('Before resolve');
				defer.resolve(self.addressBooks);
				console.log('After resolve');
			} else {
				defer.reject(response);
				$(document).trigger('status.contacts.error', response);
			}
		})
		.fail(function(response) {
			console.warn('Request Failed:', response);
			defer.reject({
				error: true,
				message: t('contacts', 'Failed loading address books: {error}', {error:response.message})
			});
		});
		return defer.promise();
	};

	OC.Contacts.AddressBookList = AddressBookList;

})(window, jQuery, OC);
