Modernizr.load({
	test: Modernizr.input.placeholder,
	nope: [
			OC.filePath('contacts', 'css', 'placeholder_polyfill.min.css'),
			OC.filePath('contacts', 'js', 'placeholder_polyfill.jquery.min.combo.js')
		]
});

(function($) {
	$.QueryString = (function(a) {
		if (a == "") return {};
		var b = {};
		for (var i = 0; i < a.length; ++i)
		{
			var p=a[i].split('=');
			if (p.length != 2) continue;
			b[p[0]] = decodeURIComponent(p[1].replace(/\+/g, " "));
		}
		return b;
	})(window.location.search.substr(1).split('&'))
})(jQuery);

var utils = {};

/**
 * utils.isArray
 *
 * Best guess if object is an array.
 */
utils.isArray = function(obj) {
     // do an instanceof check first
     if (obj instanceof Array) {
         return true;
     }
     // then check for obvious falses
     if (typeof obj !== 'object') {
         return false;
     }
     if (utils.type(obj) === 'array') {
         return true;
     }
     return false;
};

utils.isInt = function(s) {
  return typeof s === 'number' && (s.toString().search(/^-?[0-9]+$/) === 0);
};

utils.isUInt = function(s) {
  return typeof s === 'number' && (s.toString().search(/^[0-9]+$/) === 0);
};

/**
 * utils.type
 *
 * Attempt to ascertain actual object type.
 */
utils.type = function(obj) {
    if (obj === null || typeof obj === 'undefined') {
        return String (obj);
    }
    return Object.prototype.toString.call(obj)
        .replace(/\[object ([a-zA-Z]+)\]/, '$1').toLowerCase();
};

utils.moveCursorToEnd = function(el) {
	if (typeof el.selectionStart === 'number') {
		el.selectionStart = el.selectionEnd = el.value.length;
	} else if (typeof el.createTextRange !== 'undefined') {
		el.focus();
		var range = el.createTextRange();
		range.collapse(false);
		range.select();
	}
};

Array.prototype.clone = function() {
  return this.slice(0);
};

Array.prototype.clean = function(deleteValue) {
	var arr = this.clone();
	for (var i = 0; i < arr.length; i++) {
		if (arr[i] == deleteValue) {
			arr.splice(i, 1);
			i--;
		}
	}
	return arr;
};

// Keep it DRY ;)
var wrongKey = function(event) {
	return ((event.type === 'keydown' || event.type === 'keypress') 
		&& (event.keyCode !== 32 && event.keyCode !== 13));
};

/**
 * Simply notifier
 * Arguments:
 * @param message The text message to show.
 * @param timeout The timeout in seconds before the notification disappears. Default 10.
 * @param timeouthandler A function to run on timeout.
 * @param clickhandler A function to run on click. If a timeouthandler is given it will be cancelled on click.
 * @param data An object that will be passed as argument to the timeouthandler and clickhandler functions.
 * @param cancel If set cancel all ongoing timer events and hide the notification.
 */
OC.notify = function(params) {
	var self = this;
	if(!self.notifier) {
		self.notifier = $('#notification');
	}
	if(params.cancel) {
		self.notifier.off('click');
		for(var id in self.notifier.data()) {
			if($.isNumeric(id)) {
				clearTimeout(parseInt(id));
			}
		}
		self.notifier.text('').fadeOut().removeData();
		return;
	}
	self.notifier.text(params.message);
	self.notifier.fadeIn().css('display', 'inline');
	self.notifier.on('click', function() { $(this).fadeOut();});
	var timer = setTimeout(function() {
		self.notifier.fadeOut();
		if(params.timeouthandler && $.isFunction(params.timeouthandler)) {
			params.timeouthandler(self.notifier.data(dataid));
			self.notifier.off('click');
			self.notifier.removeData(dataid);
		}
	}, params.timeout && $.isNumeric(params.timeout) ? parseInt(params.timeout)*1000 : 10000);
	var dataid = timer.toString();
	if(params.data) {
		self.notifier.data(dataid, params.data);
	}
	if(params.clickhandler && $.isFunction(params.clickhandler)) {
		self.notifier.on('click', function() {
			clearTimeout(timer);
			self.notifier.off('click');
			params.clickhandler(self.notifier.data(dataid));
			self.notifier.removeData(dataid);
		});
	}
};


OC.Contacts = OC.Contacts || {
	init:function() {
		if(oc_debug === true) {
		$.error = console.error;
		$(document).ajaxError(function(e, xhr, settings, exception) {
				console.error('Error in: ', settings.url, ' : ', xhr.responseText, exception);
			});
		}
		var self = this;

		this.scrollTimeoutMiliSecs = 100;
		this.isScrolling = false;
		this.cacheElements();
		this.storage = new OC.Contacts.Storage();
		this.addressBooks = new OC.Contacts.AddressBookList(
			this.storage,
			$('#app-settings-content'),
			$('#addressbookTemplate')
		);
		this.contacts = new OC.Contacts.ContactList(
			this.storage,
			this.addressBooks,
			this.$contactList,
			this.$contactListItemTemplate,
			this.$contactDragItemTemplate,
			this.$contactFullTemplate,
			this.detailTemplates
		);
		this.groups = new OC.Contacts.GroupList(
			this.storage,
			this.$groupList,
			this.$groupListItemTemplate
		);
		self.groups.loadGroups(function() {
			self.loading(0, self.$navigation, false);
		});
		$.when(this.addressBooks.loadAddressBooks()).then(function(addressBooks) {
			var num = addressBooks.length;
			var deferreds = $(addressBooks).map(function(i, elem) {
				return self.contacts.loadContacts(this.getBackend(), this.getId());
			});
			// This little beauty is from http://stackoverflow.com/a/6162959/373007 ;)
			$.when.apply(null, deferreds.get()).then(function(response) {
				self.contacts.doSort();
				$(document).trigger('status.contacts.loaded', {
					status: true,
					numcontacts: self.contacts.length
				});
				self.loading(self.$rightContent, false);
				// TODO: Move this to event handler
				self.groups.selectGroup({id:contacts_lastgroup});
				var id = $.QueryString['id']; // Keep for backwards compatible links.
				if(!id) {
					id = window.location.hash.substr(1);
				}
				console.log('Groups loaded, id from url:', id);
				if(id) {
					self.openContact(id);
				}
				if(!contacts_properties_indexed) {
					// Wait a couple of mins then check if contacts are indexed.
					setTimeout(function() {
							$.when($.post(OC.Router.generate('contacts_index_properties')))
								.then(function(response) {
									if(!response.isIndexed) {
										OC.notify({message:t('contacts', 'Indexing contacts'), timeout:20});
									}
								});
					}, 10000);
				} else {
					console.log('contacts are indexed.');
				}
			}).fail(function(response) {
				console.warn(response);
			});
		}).fail(function(response) {
			console.log(response.message);
			$(document).trigger('status.contact.error', {
				message: response.message
			});
		});
		OCCategories.changed = this.groups.categoriesChanged;
		OCCategories.app = 'contacts';
		OCCategories.type = 'contact';
		this.bindEvents();
		this.$toggleAll.show();
		this.hideActions();
	},
	loading:function(obj, state) {
		$(obj).toggleClass('loading', state);
	},
	/**
	 * Show/hide elements in the header
	 * @param act An array of actions to show based on class name e.g ['add', 'delete']
	 */
	hideActions:function() {
		this.showActions(false);
	},
	showActions:function(act) {
		console.log('showActions', act);
		//console.trace();
		this.$headeractions.children().hide();
		if(act && act.length > 0) {
			this.$contactList.addClass('multiselect');
			this.$contactListHeader.show();
			this.$headeractions.children('.'+act.join(',.')).show();
		} else {
			this.$contactListHeader.hide();
			this.$contactList.removeClass('multiselect');
		}
	},
	showAction:function(act, show) {
		this.$headeractions.find('.' + act).toggle(show);
	},
	cacheElements: function() {
		var self = this;
		this.detailTemplates = {};
		// Load templates for contact details.
		// The weird double loading is because jquery apparently doesn't
		// create a searchable object from a script element.
		$.each($($('#contactDetailsTemplate').html()), function(idx, node) {
			var $node = $(node);
			if($node.is('div')) {
				var $tmpl = $(node.innerHTML);
				self.detailTemplates[$tmpl.data('element')] = $node;
			}
		});
		this.$groupListItemTemplate = $('#groupListItemTemplate');
		this.$contactListItemTemplate = $('#contactListItemTemplate');
		this.$contactDragItemTemplate = $('#contactDragItemTemplate');
		this.$contactFullTemplate = $('#contactFullTemplate');
		this.$contactDetailsTemplate = $('#contactDetailsTemplate');
		this.$rightContent = $('#app-content');
		this.$navigation = $('#app-navigation');
		//this.$header = $('#contactsheader');
		this.$groupList = $('#grouplist');
		this.$contactList = $('#contactlist');
		this.$contactListHeader = $('#contactsHeader');
		this.$headeractions = this.$contactListHeader.find('.actions');
		this.$toggleAll = this.$contactListHeader.find('.toggle');
		this.$groups = this.$headeractions.find('.groups');
		this.$ninjahelp = $('#ninjahelp');
		this.$firstRun = $('#firstrun');
		this.$settings = $('#app-settings');
	},
	// Build the select to add/remove from groups.
	buildGroupSelect: function() {
		// If a contact is open we know which categories it's in
		if(this.currentid) {
			var contact = this.contacts.findById(this.currentid);
			if(contact === null) {
				return false;
			}
			this.$groups.find('optgroup,option:not([value="-1"])').remove();
			var addopts = '', rmopts = '';
			$.each(this.groups.categories, function(i, category) {
				if(contact.inGroup(category.name)) {
					rmopts += '<option value="' + category.id + '">' + category.name + '</option>';
				} else {
					addopts += '<option value="' + category.id + '">' + category.name + '</option>';
				}
			});
			if(addopts.length) {
				$(addopts).appendTo(this.$groups)
				.wrapAll('<optgroup data-action="add" label="' + t('contacts', 'Add to...') + '"/>');
			}
			if(rmopts.length) {
				$(rmopts).appendTo(this.$groups)
				.wrapAll('<optgroup data-action="remove" label="' + t('contacts', 'Remove from...') + '"/>');
			}
		} else if(this.contacts.getSelectedContacts().length > 0) { // Otherwise add all categories to both add and remove
			this.$groups.find('optgroup,option:not([value="-1"])').remove();
			var addopts = '', rmopts = '';
			$.each(this.groups.categories, function(i, category) {
				rmopts += '<option value="' + category.id + '">' + category.name + '</option>';
				addopts += '<option value="' + category.id + '">' + category.name + '</option>';
			});
			$(addopts).appendTo(this.$groups)
				.wrapAll('<optgroup data-action="add" label="' + t('contacts', 'Add to...') + '"/>');
			$(rmopts).appendTo(this.$groups)
				.wrapAll('<optgroup data-action="remove" label="' + t('contacts', 'Remove from...') + '"/>');
		} else {
			// 3rd option: No contact open, none checked, just show "Add group..."
			this.$groups.find('optgroup,option:not([value="-1"])').remove();
		}
		$('<option value="add">' + t('contacts', 'Add group...') + '</option>').appendTo(this.$groups);
		this.$groups.val(-1);
	},
	bindEvents: function() {
		var self = this;

		// Should fix Opera check for delayed delete.
		$(window).unload(function (){
			$(window).trigger('beforeunload');
		});

		this.hashChange = function() {
			console.log('hashchange', window.location.hash)
			var id = String(window.location.hash.substr(1));
			if(id && id != self.currentid && self.contacts.findById(id) !== null) {
				self.openContact(id);
			} else if(!id && self.currentid) {
				self.closeContact(self.currentid);
			}
		}

		// This apparently get's called on some weird occasions.
		$(window).bind('popstate', this.hashChange);
		$(window).bind('hashchange', this.hashChange);
		
		// App specific events
		$(document).bind('status.contact.deleted', function(e, data) {
			var id = String(data.id);
			if(id == self.currentid) {
				delete self.currentid;
			}
			console.log('contact', data.id, 'deleted');
			// update counts on group lists
			self.groups.removeFromAll(data.id, true);
		});

		$(document).bind('status.contact.added', function(e, data) {
			self.currentid = String(data.id);
			self.buildGroupSelect();
			self.hideActions();
		});

		// Keep error messaging at one place to be able to replace it.
		$(document).bind('status.contact.error', function(e, data) {
			console.warn(data.message);
			//console.trace();
			OC.notify({message:data.message});
		});

		$(document).bind('status.contact.enabled', function(e, enabled) {
			console.log('status.contact.enabled', enabled);
			/*if(enabled) {
				self.showActions(['back', 'download', 'delete', 'groups']);
			} else {
				self.showActions(['back']);
			}*/
		});

		$(document).bind('status.contacts.loaded', function(e, response) {
			console.log('status.contacts.loaded', response);
			if(response.error) {
				$(document).trigger('status.contact.error', {
					message: response.message
				});
				console.log('Error loading contacts!');
			}
		});

		$(document).bind('status.contact.currentlistitem', function(e, result) {
			//console.log('status.contact.currentlistitem', result, self.$rightContent.height());
			if(self.dontScroll !== true) {
				if(result.pos > self.$rightContent.height()) {
					self.$rightContent.scrollTop(result.pos - self.$rightContent.height() + result.height);
				}
				else if(result.pos < self.$rightContent.offset().top) {
					self.$rightContent.scrollTop(result.pos);
				}
			} else {
				setTimeout(function() {
					self.dontScroll = false;
				}, 100);
			}
			self.currentlistid = result.id;
		});

		$(document).bind('status.nomorecontacts', function(e, result) {
			console.log('status.nomorecontacts', result);
			self.$contactList.hide();
			self.$firstRun.show();
		});

		$(document).bind('status.visiblecontacts', function(e, result) {
			console.log('status.visiblecontacts', result);
			// TODO: To be decided.
		});

		// A contact id was in the request
		$(document).bind('request.loadcontact', function(e, result) {
			console.log('request.loadcontact', result);
			if(self.numcontacts) {
				self.openContact(result.id);
			} else {
				// Contacts are not loaded yet, try again.
				console.log('waiting for contacts to load');
				setTimeout(function() {
					$(document).trigger('request.loadcontact', {
						id: result.id
					});
				}, 1000);
			}
		});

		$(document).bind('request.contact.move', function(e, data) {
			console.log('contact', data, 'request.contact.move');
			var from = self.addressBooks.find(data.from);
			var to = self.addressBooks.find(data.target);
			console.log('From:', from);
			console.log('To:', to);
			self.addressBooks.moveContact(data.contact, data.from, data.target);
		});

		$(document).bind('request.contact.setasfavorite', function(e, data) {
			console.log('contact', data.id, 'request.contact.setasfavorite');
			self.groups.setAsFavorite(data.id, data.state);
		});

		$(document).bind('request.contact.addtogroup', function(e, data) {
			self.groups.addTo(data.id, data.groupid, function(response) {
				console.log('contact', data.id, 'request.contact.addtogroup', response);
			});
		});

		$(document).bind('request.contact.removefromgroup', function(e, data) {
			console.log('contact', data.id, 'request.contact.removefromgroup');
			self.groups.removeFrom(data.id, data.groupid);
		});

		$(document).bind('request.contact.export', function(e, data) {
			var id = String(data.id);
			console.log('contact', data.id, 'request.contact.export');
			document.location.href = OC.linkTo('contacts', 'export.php') + '?' + $.param(data);
		});

		$(document).bind('request.contact.close', function(e, data) {
			var id = String(data.id);
			console.log('contact', data.id, 'request.contact.close');
			self.closeContact(id);
		});

		$(document).bind('request.contact.delete', function(e, data) {
			var id = String(data.contactid);
			console.log('contact', data, 'request.contact.delete');
			self.closeContact(id);
			self.contacts.delayedDelete(data);
			self.$contactList.removeClass('dim');
			self.hideActions();
		});

		$(document).bind('request.contact.merge', function(e, data) {
			console.log('contact','request.contact.merge', data);
			var merger = self.contacts.findById(data.merger);
			var mergees = [];
			if(!merger) {
				$(document).trigger('status.contact.error', {
					message: t('contacts', 'Merge failed. Cannot find contact: {id}', {id:data.merger})
				});
				return;
			}
			$.each(data.mergees, function(idx, id) {
				var contact = self.contacts.findById(id);
				if(!contact) {
					console.warn('cannot find', id, 'by id');
				}
				mergees.push(contact);
			});
			if(!merger.merge(mergees)) {
				$(document).trigger('status.contact.error', {
					message: t('contacts', 'Merge failed.')
				});
				return;
			}
			merger.saveAll(function(response) {
				if(response.error) {
					$(document).trigger('status.contact.error', {
						message: t('contacts', 'Merge failed. Error saving contact.')
					});
					return;
				} else {
					if(data.deleteOther) {
						self.contacts.delayedDelete(mergees);
					}
					self.openContact(merger.getId());
				}
			});
		});

		$(document).bind('request.select.contactphoto.fromlocal', function(e, metadata) {
			console.log('request.select.contactphoto.fromlocal', metadata);
			$('#contactphoto_fileupload').trigger('click', metadata);
		});

		$(document).bind('request.select.contactphoto.fromcloud', function(e, metadata) {
			console.log('request.select.contactphoto.fromcloud', metadata);
			OC.dialogs.filepicker(t('contacts', 'Select photo'), function(path) {
				self.cloudPhotoSelected(metadata, path);
			}, false, 'image', true);
		});

		$(document).bind('request.edit.contactphoto', function(e, metadata) {
			console.log('request.edit.contactphoto', metadata);
			self.editCurrentPhoto(metadata);
		});

		$(document).bind('request.addressbook.activate', function(e, result) {
			console.log('request.addressbook.activate', result);
			self.contacts.showFromAddressbook(result.id, result.activate);
		});

		$(document).bind('request.groups.reload', function(e, result) {
			console.log('request.groups.reload', result);
			self.groups.loadGroups();
		});

		$(document).bind('status.contact.removedfromgroup', function(e, result) {
			console.log('status.contact.removedfromgroup', result);
			if(self.currentgroup == result.groupid) {
				self.contacts.hideContact(result.contactid);
				self.closeContact(result.contactid);
			}
		});

		$(document).bind('status.group.groupremoved', function(e, result) {
			console.log('status.group.groupremoved', result);
			if(parseInt(result.groupid) === parseInt(self.currentgroup)) {
				self.contacts.showContacts([]);
				self.currentgroup = 'all';
			}
			$.each(result.contacts, function(idx, contactid) {
				var contact = self.contacts.findById(contactid);

				contact.removeFromGroup(result.groupname);
			});
		});

		$(document).bind('status.group.contactadded', function(e, result) {
			console.log('status.group.contactadded', result);
			var contact = self.contacts.findById(result.contactid);
			if(!contact) {
				return false;
			}
			contact.addToGroup(result.groupname);
		});

		// Group sorted, save the sort order
		$(document).bind('status.groups.sorted', function(e, result) {
			console.log('status.groups.sorted', result);
			$.when(self.storage.setPreference('groupsort', result.sortorder)).then(function(response) {
				if(response.error) {
					OC.notify({message: response ? response.message : t('contacts', 'Network or server error. Please inform administrator.')});
				}
			})
			.fail(function(jqxhr, textStatus, error) {
				var err = textStatus + ', ' + error;
				console.log( "Request Failed: " + err);
				$(document).trigger('status.contact.error', {
					message: t('contacts', 'Failed saving sort order: {error}', {error:err})
				});
			});
		});
		// Group selected, only show contacts from that group
		$(document).bind('status.group.selected', function(e, result) {
			console.log('status.group.selected', result);
			self.currentgroup = result.id;
			// Close any open contact.
			if(self.currentid) {
				var id = self.currentid;
				self.closeContact(id);
				self.jumpToContact(id);
			}
			self.$contactList.show();
			self.$toggleAll.show();
			self.hideActions();
			if(result.type === 'category' ||  result.type === 'fav') {
				self.contacts.showContacts(result.contacts);
			} else if(result.type === 'shared') {
				self.contacts.showFromAddressbook(self.currentgroup, true, true);
			} else {
				self.contacts.showContacts(self.currentgroup);
			}
			$.when(self.storage.setPreference('lastgroup', self.currentgroup)).then(function(response) {
				if(response.error) {
					OC.notify({message: response.message});
				}
			})
			.fail(function(jqxhr, textStatus, error) {
				var err = textStatus + ', ' + error;
				console.log( "Request Failed: " + err);
				$(document).trigger('status.contact.error', {
					message: t('contacts', 'Failed saving last group: {error}', {error:err})
				});
			});
			self.$rightContent.scrollTop(0);
		});
		// mark items whose title was hid under the top edge as read
		/*this.$rightContent.scroll(function() {
			// prevent too many scroll requests;
			if(!self.isScrolling) {
				self.isScrolling = true;
				var num = self.$contactList.find('tr').length;
				//console.log('num', num);
				var offset = self.$contactList.find('tr:eq(' + (num-20) + ')').offset().top;
				if(offset < self.$rightContent.height()) {
					console.log('load more');
					self.contacts.loadContacts(num, function() {
						self.isScrolling = false;
					});
				} else {
					setTimeout(function() {
						self.isScrolling = false;
					}, self.scrollTimeoutMiliSecs);
				}
				//console.log('scroll, unseen:', offset, self.$rightContent.height());
			}
		});*/
		$('#contactphoto_fileupload').on('click', function(event, metadata) {
			var form = $('#file_upload_form');
			form.find('input[name="contactid"]').val(metadata.contactid);
			form.find('input[name="addressbookid"]').val(metadata.addressbookid);
			form.find('input[name="backend"]').val(metadata.backend);
		});

		$('#contactphoto_fileupload').on('change', function() {
			self.uploadPhoto(this.files);
		});

		this.$ninjahelp.find('.close').on('click keydown',function(event) {
			if(wrongKey(event)) {
				return;
			}
			self.$ninjahelp.hide();
		});

		this.$toggleAll.on('change', function() {
			var isChecked = $(this).is(':checked');
			self.setAllChecked(isChecked);
			if(self.$groups.find('option').length === 1) {
				self.buildGroupSelect();
			}
			if(isChecked) {
				self.showActions(['add', 'download', 'groups', 'delete', 'favorite', 'merge']);
			} else {
				self.hideActions();
			}
		});

		this.$contactList.on('change', 'input:checkbox', function(event) {
			var selected = self.contacts.getSelectedContacts();
			console.log('selected', selected.length);
			if(selected.length > 0 && self.$groups.find('option').length === 1) {
				self.buildGroupSelect();
			}
			if(selected.length === 0) {
				self.hideActions();
			} else if(selected.length === 1) {
				self.showActions(['add', 'download', 'groups', 'delete', 'favorite']);
			} else {
				self.showActions(['add', 'download', 'groups', 'delete', 'favorite', 'merge']);
			}
		});

		// Add to/remove from group multiple contacts.
		this.$groups.on('change', function() {
			var $opt = $(this).find('option:selected');
			var action = $opt.parent().data('action');
			var groupName, groupId, buildnow = false;

			var contacts = self.contacts.getSelectedContacts();
			var ids = $.map(contacts, function(c) {return c.getId();});

			self.setAllChecked(false);
			self.$toggleAll.prop('checked', false);
			if(!self.currentid) {
				self.hideActions();
			}

			if($opt.val() === 'add') { // Add new group
				action = 'add';
				console.log('add group...');
				self.$groups.val(-1);
				self.addGroup(function(response) {
					if(!response.error) {
						groupId = response.id;
						groupName = response.name;
						self.groups.addTo(ids, groupId, function(result) {
							if(!result.error) {
								$.each(ids, function(idx, id) {
									// Delay each contact to not trigger too many ajax calls
									// at a time.
									setTimeout(function() {
										var contact = self.contacts.findById(id);
										if(contact === null) {
											return true;
										}
										contact.addToGroup(groupName);
										// I don't think this is used...
										if(buildnow) {
											self.buildGroupSelect();
										}
										$(document).trigger('status.contact.addedtogroup', {
											contactid: id,
											groupid: groupId,
											groupname: groupName
										});
									}, 1000);
								});
							} else {
								OC.notify({message:result.message});
							}
						});
					} else {
						OC.notify({message: response.message});
					}
				});
				return;
			}

			groupName = $opt.text(), groupId = $opt.val();

			if(action === 'add') {
				self.groups.addTo(ids, $opt.val(), function(result) {
					console.log('after add', result);
					if(!result.error) {
						$.each(result.ids, function(idx, id) {
							// Delay each contact to not trigger too many ajax calls
							// at a time.
							setTimeout(function() {
								console.log('adding', id, 'to', groupName);
								var contact = self.contacts.findById(id);
								if(contact === null) {
									return true;
								}
								contact.addToGroup(groupName);
								// I don't think this is used...
								if(buildnow) {
									self.buildGroupSelect();
								}
								$(document).trigger('status.contact.addedtogroup', {
									contactid: id,
									groupid: groupId,
									groupname: groupName
								});
							}, 1000);
						});
					} else {
						var msg = result.message ? result.message : t('contacts', 'Error adding to group.');
						OC.notify({message:msg});
					}
				});
				if(!buildnow) {
					self.$groups.val(-1).hide().find('optgroup,option:not([value="-1"])').remove();
				}
			} else if(action === 'remove') {
				self.groups.removeFrom(ids, $opt.val(), function(result) {
					console.log('after remove', result);
					if(!result.error) {
						var groupname = $opt.text(), groupid = $opt.val();
						$.each(result.ids, function(idx, id) {
							var contact = self.contacts.findById(id);
							if(contact === null) {
								return true;
							}
							contact.removeFromGroup(groupname);
							if(buildnow) {
								self.buildGroupSelect();
							}
							// If a group is selected the contact has to be removed from the list
							$(document).trigger('status.contact.removedfromgroup', {
								contactid: id,
								groupid: groupId,
								groupname: groupName
							});
						});
					} else {
						var msg = result.message ? result.message : t('contacts', 'Error removing from group.');
						OC.notify({message:msg});
					}
				});
				if(!buildnow) {
					self.$groups.val(-1).hide().find('optgroup,option:not([value="-1"])').remove();
				}
			} // else something's wrong ;)
			self.setAllChecked(false);
		});

		// Contact list. Either open a contact or perform an action (mailto etc.)
		this.$contactList.on('click', 'tr', function(event) {
			if($(event.target).is('input')) {
				return;
			}
			if(event.ctrlKey || event.metaKey) {
				event.stopPropagation();
				event.preventDefault();
				self.dontScroll = true;
				self.contacts.select($(this).data('id'), true);
				return;
			}
			if($(event.target).is('a.mailto')) {
				var mailto = 'mailto:' + $.trim($(this).find('.email').text());
				try {
					window.location.href=mailto;
				} catch(e) {
					alert(t('contacts', 'There was an error opening a mail composer.'));
				}
				return;
			}
			self.openContact(String($(this).data('id')));
		});

		this.$settings.find('#app-settings-header').on('click keydown',function(event) {
			if(wrongKey(event)) {
				return;
			}
			var bodyListener = function(e) {
				if(self.$settings.find($(e.target)).length == 0) {
					self.$settings.switchClass('open', '');
				}
			};
			if(self.$settings.hasClass('open')) {
				self.$settings.switchClass('open', '');
				$('body').unbind('click', bodyListener);
			} else {
				self.$settings.switchClass('', 'open');
				$('body').bind('click', bodyListener);
			}
		});

		var addContact = function() {
			console.log('add');
			self.$toggleAll.hide();
			$(this).hide();
			self.currentid = 'new';
			// Properties that the contact doesn't know
			console.log('addContact, groupid', self.currentgroup);
			self.$contactList.addClass('dim');
			var groupprops = {
				favorite: false,
				groups: self.groups.categories,
				currentgroup: {id:self.currentgroup, name:self.groups.nameById(self.currentgroup)}
			};
			self.$firstRun.hide();
			self.$contactList.show();
			self.$contactList.addClass('dim');
			self.tmpcontact = self.contacts.addContact(groupprops);
			self.$rightContent.prepend(self.tmpcontact);
			self.hideActions();
		};

		this.$firstRun.on('click keydown', '.import', function(event) {
			event.preventDefault();
			event.stopPropagation();
			self.$settings.find('.settings').click();
		});

		this.$firstRun.on('click keydown', '.add-contact', function(event) {
			if(wrongKey(event)) {
				return;
			}
			addContact();
		});

		this.$groupList.on('click keydown', '.add-contact', function(event) {
			if(wrongKey(event)) {
				return;
			}
			addContact();
		});

		this.$contactListHeader.on('click keydown', '.delete', function(event) {
			if(wrongKey(event)) {
				return;
			}
			console.log('delete');
			if(self.currentid) {
				console.assert(typeof self.currentid === 'string', 'self.currentid is not a string');
				contactInfo = self.contacts[self.currentid].metaData();
				self.contacts.delayedDelete(contactInfo);
			} else {
				self.contacts.delayedDelete(self.contacts.getSelectedContacts());
			}
			self.hideActions();
		});

		this.$contactListHeader.on('click keydown', '.download', function(event) {
			if(wrongKey(event)) {
				return;
			}
			console.log('download');
			var contacts = self.contacts.getSelectedContacts();
			var ids = $.map(contacts, function(c) {return c.getId();});
			document.location.href = OC.linkTo('contacts', 'export.php')
				+ '?selectedids=' + ids.join(',');
		});

		this.$contactListHeader.on('click keydown', '.merge', function(event) {
			if(wrongKey(event)) {
				return;
			}
			console.log('merge');
			self.mergeSelectedContacts();
		});

		this.$contactListHeader.on('click keydown', '.favorite', function(event) {
			if(wrongKey(event)) {
				return;
			}

			var contacts = self.contacts.getSelectedContacts();

			self.setAllChecked(false);
			self.$toggleAll.prop('checked', false);
			if(!self.currentid) {
				self.hideActions();
			}

			$.each(contacts, function(idx, contact) {
				if(!self.groups.isFavorite(contact.getId())) {
					self.groups.setAsFavorite(contact.getId(), true, function(result) {
						if(result.status !== 'success') {
							OC.notify({message:
								t('contacts',
									'Error setting {name} as favorite.',
									{name:contact.getDisplayName()})
							});
						}
					});
				}
			});

			self.hideActions();
		});

		this.$contactList.on('mouseenter', 'td.email', function(event) {
			if($.trim($(this).text()).length > 3) {
				$(this).find('.mailto').css('display', 'inline-block'); //.fadeIn(100);
			}
		});
		this.$contactList.on('mouseleave', 'td.email', function(event) {
			$(this).find('.mailto').fadeOut(100);
		});

		$('body').on('touchmove', function(event) {
			event.preventDefault();
		});
		
		$(document).on('keyup', function(event) {
			if(!$(event.target).is('body') || event.isPropagationStopped()) {
				return;
			}
			var keyCode = Math.max(event.keyCode, event.which);
			// TODO: This should go in separate method
			console.log(event, keyCode + ' ' + event.target.nodeName);
			/**
			* To add:
			* Shift-a: add addressbook
			* u (85): hide/show leftcontent
			* f (70): add field
			*/
			switch(keyCode) {
				case 13: // Enter?
					console.log('Enter?');
					if(!self.currentid && self.currentlistid) {
						self.openContact(self.currentlistid);
					}
					break;
				case 27: // Esc
					if(self.$ninjahelp.is(':visible')) {
						self.$ninjahelp.hide();
					} else if(self.currentid) {
						self.closeContact(self.currentid);
					}
					break;
				case 46: // Delete
					if(event.shiftKey) {
						self.contacts.delayedDelete(self.currentid);
					}
					break;
				case 40: // down
				case 74: // j
					console.log('next');
					if(!self.currentid && self.currentlistid) {
						self.contacts.contacts[self.currentlistid].next();
					}
					break;
				case 65: // a
					if(event.shiftKey) {
						console.log('add group?');
						break;
					}
					self.addContact();
					break;
				case 38: // up
				case 75: // k
					console.log('previous');
					if(!self.currentid && self.currentlistid) {
						self.contacts.contacts[self.currentlistid].prev();
					}
					break;
				case 34: // PageDown
				case 78: // n
					console.log('page down');
					break;
				case 79: // o
					console.log('open contact?');
					break;
				case 33: // PageUp
				case 80: // p
					// prev addressbook
					//OC.contacts.contacts.previousAddressbook();
					break;
				case 82: // r
					console.log('refresh - what?');
					break;
				case 63: // ? German.
					if(event.shiftKey) {
						self.$ninjahelp.toggle('fast');
					}
					break;
				case 171: // ? Danish
				case 191: // ? Standard qwerty
					self.$ninjahelp.toggle('fast').position({my: "center",at: "center",of: "#content"});
					break;
			}

		});

		// find all with a title attribute and tipsy them
		$('.tooltipped.downwards:not(.onfocus)').tipsy({gravity: 'n'});
		$('.tooltipped.upwards:not(.onfocus)').tipsy({gravity: 's'});
		$('.tooltipped.rightwards:not(.onfocus)').tipsy({gravity: 'w'});
		$('.tooltipped.leftwards:not(.onfocus)').tipsy({gravity: 'e'});
		$('.tooltipped.downwards.onfocus').tipsy({trigger: 'focus', gravity: 'n'});
		$('.tooltipped.rightwards.onfocus').tipsy({trigger: 'focus', gravity: 'w'});
	},
	mergeSelectedContacts: function() {
		var contacts = this.contacts.getSelectedContacts();
		var self = this;
		this.$rightContent.append('<div id="merge_contacts_dialog"></div>');
		if(!this.$mergeContactsTmpl) {
			this.$mergeContactsTmpl = $('#mergeContactsTemplate');
		}
		var $dlg = this.$mergeContactsTmpl.octemplate();
		var $liTmpl = $dlg.find('li').detach();
		var $mergeList = $dlg.find('.mergelist');
		$.each(contacts, function(idx, contact) {
			var $li = $liTmpl
				.octemplate({idx: idx, id: contact.getId(), displayname: contact.getDisplayName()});
			if(!contact.data.thumbnail) {
				$li.addClass('thumbnail');
			} else {
				$li.css('background-image', 'url(data:image/png;base64,' + contact.data.thumbnail + ')');
			}
			if(idx === 0) {
				$li.find('input:radio').prop('checked', true);
			}
			$mergeList.append($li);
		});
		this.$contactList.addClass('dim');
		$('#merge_contacts_dialog').html($dlg).ocdialog({
			closeOnEscape: true,
			title:  t('contacts', 'Merge contacts'),
			height: 'auto', width: 'auto',
			buttons: [
				{
					text: t('contacts', 'Merge contacts'),
					click:function() {
						// Do the merging, use $(this) to get dialog
						var contactid = $(this).find('input:radio:checked').val();
						var others = [];
						var deleteOther = $(this).find('#delete_other').prop('checked');
						console.log('Selected contact', contactid, 'Delete others', deleteOther);
						$.each($(this).find('input:radio:not(:checked)'), function(idx, item) {
							others.push($(item).val());
						});
						console.log('others', others);
						$(document).trigger('request.contact.merge', {
							merger: contactid,
							mergees: others,
							deleteOther: deleteOther
						});

						$(this).ocdialog('close');
					},
					defaultButton: true
				},
				{
					text: t('contacts', 'Cancel'),
					click:function(dlg) {
						$(this).ocdialog('close');
						return false;
					}
				}
			],
			close: function(event, ui) {
				$(this).ocdialog('destroy').remove();
				$('#add_group_dialog').remove();
				self.$contactList.removeClass('dim');
			},
			open: function(event, ui) {
				$dlg.find('input').focus();
			}
		});
	},
	addGroup: function(cb) {
		var self = this;
		this.$rightContent.append('<div id="add_group_dialog"></div>');
		if(!this.$addGroupTmpl) {
			this.$addGroupTmpl = $('#addGroupTemplate');
		}
		this.$contactList.addClass('dim');
		var $dlg = this.$addGroupTmpl.octemplate();
		$('#add_group_dialog').html($dlg).ocdialog({
			modal: true,
			closeOnEscape: true,
			title:  t('contacts', 'Add group'),
			height: 'auto', width: 'auto',
			buttons: [
				{
					text: t('contacts', 'OK'),
					click:function() {
						var name = $(this).find('input').val();
						if(name.trim() === '') {
							return false;
						}
						self.groups.addGroup(
							{name:$dlg.find('input:text').val()},
							function(response) {
								if(typeof cb === 'function') {
									cb(response);
								} else {
									if(response.error) {
										OC.notify({message: response.message});
									}
								}
							});
						$(this).ocdialog('close');
					},
					defaultButton: true
				},
				{
					text: t('contacts', 'Cancel'),
					click:function(dlg) {
						$(this).ocdialog('close');
						return false;
					}
				}
			],
			close: function(event, ui) {
				$(this).ocdialog('destroy').remove();
				$('#add_group_dialog').remove();
				self.$contactList.removeClass('dim');
			},
			open: function(event, ui) {
				$dlg.find('input').focus();
			}
		});
	},
	setAllChecked: function(checked) {
		var selector = checked ? 'input:checkbox:visible:not(checked)' : 'input:checkbox:visible:checked';
		$.each(this.$contactList.find(selector), function() {
			$(this).prop('checked', checked);
		});
	},
	jumpToContact: function(id) {
		this.$rightContent.scrollTop(this.contacts.contactPos(id)-30);
	},
	closeContact: function(id) {
		$(window).unbind('hashchange', this.hashChange);
		if(this.currentid === 'new') {
			this.tmpcontact.remove();
			this.$contactList.show();
		} else {
			var contact = this.contacts.findById(id);
			if(contact && contact.close()) {
				this.jumpToContact(id);
			}
		}
		this.$contactList.removeClass('dim');
		delete this.currentid;
		this.hideActions();
		this.$groups.find('optgroup,option:not([value="-1"])').remove();
		if(this.contacts.length === 0) {
			$(document).trigger('status.nomorecontacts');
		}
		//$('body').unbind('click', this.bodyListener);
		window.location.hash = '';
		$(window).bind('hashchange', this.hashChange);
	},
	openContact: function(id) {
		if(typeof id == 'undefined' || id == 'undefined') {
			console.warn('id is undefined!');
			console.trace();
		}
		this.hideActions();
		console.log('Contacts.openContact', id, typeof id);
		if(this.currentid && this.currentid !== id) {
			this.closeContact(this.currentid);
		}
		$(window).unbind('hashchange', this.hashChange);
		this.currentid = id;
		console.log('Contacts.openContact, Favorite', this.currentid, this.groups.isFavorite(this.currentid), this.groups);
		this.setAllChecked(false);
		//this.$contactList.hide();
		this.$contactList.addClass('dim');
		this.$toggleAll.hide();
		console.assert(typeof this.currentid === 'string', 'Current ID not string');
		this.jumpToContact(this.currentid);
		// Properties that the contact doesn't know
		var groupprops = {
			favorite: this.groups.isFavorite(this.currentid),
			groups: this.groups.categories,
			currentgroup: {id:this.currentgroup, name:this.groups.nameById(this.currentgroup)}
		};
		var $contactelem = this.contacts.showContact(this.currentid, groupprops);
		if(!$contactelem) {
			console.warn('Error opening', this.currentid);
			this.$contactList.removeClass('dim');
			$(document).trigger('status.contact.error', {
				message: t('contacts', 'Could not find contact: {id}', {id:this.currentid})
			});
			this.currentid = null;
			return;
		}
		var self = this;
		var $contact = $contactelem.find('#contact');
		var adjustElems = function() {
			var maxheight = document.documentElement.clientHeight - 200; // - ($contactelem.offset().top+70);
			console.log('contact maxheight', maxheight);
			$contactelem.find('ul').first().css({'max-height': maxheight, 'overflow-y': 'auto', 'overflow-x': 'hidden'});
		};
		$(window).resize(adjustElems);
		//$contact.resizable({ minWidth: 400, minHeight: 400, maxHeight: maxheight});
		this.$rightContent.prepend($contactelem);
		adjustElems();
		/*this.bodyListener = function(e) {
			if(!self.currentid) {
				return;
			}
			var $contactelem = self.contacts.findById(self.currentid).$fullelem;
			if($contactelem.find($(e.target)).length === 0) {
				self.closeContact(self.currentid);
			}
		};*/
		window.location.hash = this.currentid.toString();
		setTimeout(function() {
			//$('body').bind('click', self.bodyListener);
			$(window).bind('hashchange', this.hashChange);
		}, 500);
	},
	update: function() {
		console.log('update');
	},
	uploadPhoto:function(filelist) {
		var self = this;
		if(!filelist) {
			OC.notify({message:t('contacts','No files selected for upload.')});
			return;
		}
		var file = filelist[0];
		var target = $('#file_upload_target');
		var form = $('#file_upload_form');
		var totalSize=0;
		if(file.size > $('#max_upload').val()){
			OC.notify({
				message:t(
					'contacts',
					'The file you are trying to upload exceed the maximum size for file uploads on this server.')
			});
			return;
		} else {
			target.load(function() {
				var response=jQuery.parseJSON(target.contents().text());
				if(response != undefined && response.status == 'success') {
					console.log('response', response);
					self.editPhoto(
						response.metadata,
						response.data.tmp
					);
					//alert('File: ' + file.tmp + ' ' + file.name + ' ' + file.mime);
				} else {
					OC.notify({message:response.data.message});
				}
			});
			form.submit();
		}
	},
	cloudPhotoSelected:function(metadata, path) {
		var self = this;
		console.log('cloudPhotoSelected', metadata);
		$.getJSON(OC.filePath('contacts', 'ajax', 'oc_photo.php'),
				  {path: path, contact: metadata},function(jsondata) {
			if(jsondata.status == 'success') {
				//alert(jsondata.data.page);
				self.editPhoto(metadata, jsondata.data.tmp);
				//$('#edit_photo_dialog_img').html(jsondata.data.page);
			}
			else{
				OC.notify({message: jsondata.data.message});
			}
		});
	},
	editCurrentPhoto:function(metadata) {
		var self = this;
		$.getJSON(OC.filePath('contacts', 'ajax', 'currentphoto.php'), metadata,
			function(jsondata) {
			if(jsondata.status == 'success') {
				//alert(jsondata.data.page);
				self.editPhoto(metadata, jsondata.data.tmp);
				$('#edit_photo_dialog_img').html(jsondata.data.page);
			}
			else{
				OC.notify({message: jsondata.data.message});
			}
		});
	},
	editPhoto:function(metadata, tmpkey) {
		console.log('editPhoto', metadata, tmpkey);
		$('.tipsy').remove();
		// Simple event handler, called from onChange and onSelect
		// event handlers, as per the Jcrop invocation above
		var showCoords = function(c) {
			$('#x1').val(c.x);
			$('#y1').val(c.y);
			$('#x2').val(c.x2);
			$('#y2').val(c.y2);
			$('#w').val(c.w);
			$('#h').val(c.h);
		};

		var clearCoords = function() {
			$('#coords input').val('');
		};

		var self = this;
		if(!this.$cropBoxTmpl) {
			this.$cropBoxTmpl = $('#cropBoxTemplate');
		}
		$('body').append('<div id="edit_photo_dialog"></div>');
		var $dlg = this.$cropBoxTmpl.octemplate(
			{
				backend: metadata.backend,
				addressbookid: metadata.addressbookid,
				contactid: metadata.contactid,
				tmpkey: tmpkey
			});

		var cropphoto = new Image();
		$(cropphoto).load(function () {
			$(this).attr('id', 'cropbox');
			$(this).prependTo($dlg).fadeIn();
			$(this).Jcrop({
				onChange:	showCoords,
				onSelect:	showCoords,
				onRelease:	clearCoords,
				maxSize:	[399, 399],
				bgColor:	'black',
				bgOpacity:	.4,
				boxWidth:	400,
				boxHeight:	400,
				setSelect:	[ 100, 130, 50, 50 ]//,
				//aspectRatio: 0.8
			});
			$('#edit_photo_dialog').html($dlg).dialog({
							modal: true,
							closeOnEscape: true,
							title:  t('contacts', 'Edit profile picture'),
							height: 'auto', width: 'auto',
							buttons: {
								'Ok':function() {
									self.savePhoto($(this));
									$(this).dialog('close');
								},
								'Cancel':function() { $(this).dialog('close'); }
							},
							close: function(event, ui) {
								$(this).dialog('destroy').remove();
								$('#edit_photo_dialog').remove();
							},
							open: function(event, ui) {
								// Jcrop maybe?
							}
						});
		}).error(function () {
			OC.notify({message:t('contacts','Error loading profile picture.')});
		}).attr('src', OC.linkTo('contacts', 'tmpphoto.php')+'?tmpkey='+tmpkey+'&refresh='+Math.random());
	},
	savePhoto:function($dlg) {
		var form = $dlg.find('#cropform');
		q = form.serialize();
		console.log('savePhoto', q);
		$.post(OC.filePath('contacts', 'ajax', 'savecrop.php'), q, function(response) {
			//var jsondata = $.parseJSON(response);
			console.log('savePhoto, response', typeof response);
			if(response && response.status === 'success') {
				// load cropped photo.
				$(document).trigger('status.contact.photoupdated', {
					id: response.data.id,
					thumbnail: response.data.thumbnail
				});
			} else {
				if(!response) {
					OC.notify({message:t('contacts', 'Network or server error. Please inform administrator.')});
				} else {
					OC.notify({message: response.data.message});
				}
			}
		});
	},
};

$(document).ready(function() {

	OC.Router.registerLoadedCallback(function() {
		$.getScript(OC.Router.generate('contacts_jsconfig'))
		.done(function() {
			OC.Contacts.init();
		})
		.fail(function(jqxhr, settings, exception) {
			console.log('Failed loading settings.', jqxhr, settings, exception);
		});
	});

});
