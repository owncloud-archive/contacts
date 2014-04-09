OC.Contacts = OC.Contacts || {};

(function(window, $, OC) {
	'use strict';

	var OtherBackendConfig = function(storage, addressbooks, $template) {
		this.storage = storage;
		this.addressbooks = addressbooks;
		this.$template = $template;
	};
	
	OC.Contacts.OtherBackendConfig = OtherBackendConfig;

	OtherBackendConfig.prototype.openAddressbookUi = function() {
		var self = this;
		this.addressbookUiInit();
	};

	OtherBackendConfig.prototype.editAddressbookUI = function(addressbook) {
		var self = this;
		$('#addressbooks-ui-addressbookid').val(addressbook.id);
		$('#addressbooks-ui-name').val(addressbook.displayname);
		$('#addressbooks-ui-uri').val(addressbook.uri);
		$('#addressbooks-ui-description').val(addressbook.description);
		$('#addressbooks-ui-ldapurl').val(addressbook.ldapurl);
		$('#addressbooks-ui-ldapanonymous').attr('checked', (addressbook.ldapanonymous===true));
		$('#addressbooks-ui-ldapreadonly').attr('checked', (addressbook.ldapreadonly===true));
		$('#addressbooks-ui-ldapuser').val(addressbook.ldapuser);
		$('#addressbooks-ui-ldappass').val('nochange');
		$('#addressbooks-ui-ldappass-modified').val('false');
		$('#addressbooks-ui-ldappagesize').val(addressbook.ldappagesize);
		$('#addressbooks-ui-ldapbasednsearch').val(addressbook.ldapbasednsearch);
		$('#addressbooks-ui-ldapfilter').val(addressbook.ldapfilter);
		$('#addressbooks-ui-ldapbasednmodify').val(addressbook.ldapbasednmodify);
		$('#addressbooks-ui-uri').prop('disabled', true);
		if ($('#addressbooks-ui-ldapanonymous').prop('checked')) {
			$('#addressbooks-ui-ldapuser').prop('disabled', true);
			$('#addressbooks-ui-ldappass').prop('disabled', true);
		} else {
			$('#addressbooks-ui-ldapuser').removeProp('disabled');
			$('#addressbooks-ui-ldappass').removeProp('disabled');
		}
		if ($('#addressbooks-ui-ldapreadonly').prop('checked')) {
			$('#addressbooks-ui-ldapbasednmodify').prop('disabled', true);
		} else {
			$('#addressbooks-ui-ldapbasednmodify').removeProp('disabled');
		}
		
		$('#addressbooks-ui-ldappass').change(function() {
			$('#addressbooks-ui-ldappass-modified').val('true');
		});
		
		this.addressbookUiInit();

		$.when(self.storage.getConnectors($('#addressbooks-ui-backend').val()))
			.then(function(response) {
				$('#addressbooks-ui-ldapvcardconnector').empty();
				console.log('addressbook.ldapconnectorid', addressbook.ldapconnectorid);
				var custom = true;
				for (var id = 0; id < response.data.length; id++) {
					console.log('response.data[id][\'id\']', response.data[id].id);
					var $option = null;
					if (response.data[id].id === addressbook.ldapconnectorid) {
						$option = $('<option value="' + response.data[id].id + '">' + response.data[id].name + '</option>').attr('selected','selected');
						custom = false;
					} else {
						$option = $('<option value="' + response.data[id].id + '">' + response.data[id].name + '</option>');
					}
					$('#addressbooks-ui-ldapvcardconnector').append($option);
				}
				if (custom) {
					console.log("custom selected");
					var $option = $('<option value="">' + 'Custom connector' + '</option>').attr('selected','selected');
					$('#addressbooks-ui-ldapvcardconnector-value-p').show();
					$('#addressbooks-ui-ldapvcardconnector-copyfrom-p').show();
					$.when(self.storage.getConnectors($('#addressbooks-ui-backend').val()))
					.then(function(response) {
						$('#addressbooks-ui-ldapvcardconnector-copyfrom').empty();
						var $option = $('<option value="">' + 'Select connector' + '</option>').attr('selected','selected');
						$('#addressbooks-ui-ldapvcardconnector-copyfrom').append($option);
						for (var id = 0; id < response.data.length; id++) {
							$option = $('<option value="' + response.data[id].id + '">' + response.data[id].name + '</option>');
							$('#addressbooks-ui-ldapvcardconnector-copyfrom').append($option);
						}
					})
					.fail(function(jqxhr, textStatus, error) {
						var err = textStatus + ', ' + error;
						console.log('Request Failed', + err);
						defer.reject({error:true, message:error});
					});

					$('#addressbooks-ui-ldapvcardconnector-value').text(addressbook.ldap_vcard_connector);
				} else {
					console.log("custom not selected");
					var $option = $('<option value="">' + 'Custom connector' + '</option>');
				}
				$('#addressbooks-ui-ldapvcardconnector').append($option);
			})
			.fail(function(jqxhr, textStatus, error) {
				var err = textStatus + ', ' + error;
				console.log('Request Failed', + err);
				defer.reject({error:true, message:error});
			});
	};

	OtherBackendConfig.prototype.addressbookUiOk = function(divDlg) {
		var defer = $.Deferred();
		var addressbook = OC.Contacts.addressBooks;

		$.when(this.storage.addAddressBook($('#addressbooks-ui-backend').val(),
		{
			displayname: $('#addressbooks-ui-name').val(),
			description: $('#addressbooks-ui-description').val(),
			uri: ($('#addressbooks-ui-uri').val()==='')?$('#addressbooks-ui-name').val():$('#addressbooks-ui-uri').val(),
			ldapurl: $('#addressbooks-ui-ldapurl').val(),
			ldapanonymous: $('#addressbooks-ui-ldapanonymous').prop('checked')===true?'true':'false',
			ldapreadonly: $('#addressbooks-ui-ldapreadonly').prop('checked')===true?'true':'false',
			ldapuser: $('#addressbooks-ui-ldapuser').val(),
			ldappass: $('#addressbooks-ui-ldappass').val(),
			ldappagesize: $('#addressbooks-ui-ldappagesize').val(),
			ldapbasednsearch: $('#addressbooks-ui-ldapbasednsearch').val(),
			ldapfilter: $('#addressbooks-ui-ldapfilter').val(),
			ldapbasednmodify: $('#addressbooks-ui-ldapbasednmodify').val(),
			ldapvcardconnector: $('#addressbooks-ui-ldapvcardconnector').val(),
			ldapvcardconnectorvalue: $('#addressbooks-ui-ldapvcardconnector-value').val(),
		}
		)).then(function(response) {
			if(response.error) {
				error = response.message;
				if(typeof cb === 'function') {
					cb({error:true, message:error});
				}
				defer.reject(response);
			} else {
				console.log('response.data', response.data);
				var book = addressbook.insertAddressBook(response.data);
				$(document).trigger('status.addressbook.added');
				if(typeof cb === 'function') {
					cb({error:false, addressbook: book});
				}
				defer.resolve({error:false, addressbook: book});
			}
			OC.Contacts.otherBackendConfig.addressbookUiClose(divDlg);
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
			OC.Contacts.otherBackendConfig.addressbookUiClose(divDlg);
		});
	};

	OtherBackendConfig.prototype.addressbookUiEditOk = function(divDlg) {
		var defer = $.Deferred();

		$.when(this.storage.updateAddressBook($('#addressbooks-ui-backend').val(), $('#addressbooks-ui-addressbookid').val(),
		{properties:
			{
				displayname: $('#addressbooks-ui-name').val(),
				description: $('#addressbooks-ui-description').val(),
				uri: $('#addressbooks-ui-uri').val(),
				ldapurl: $('#addressbooks-ui-ldapurl').val(),
				ldapanonymous: $('#addressbooks-ui-ldapanonymous').prop('checked')===true?'true':'false',
				ldapreadonly: $('#addressbooks-ui-ldapreadonly').prop('checked')===true?'true':'false',
				ldapuser: $('#addressbooks-ui-ldapuser').val(),
				ldappassmodified: $('#addressbooks-ui-ldappass-modified').val(),
				ldappass: $('#addressbooks-ui-ldappass').val(),
				ldappagesize: $('#addressbooks-ui-ldappagesize').val(),
				ldapbasednsearch: $('#addressbooks-ui-ldapbasednsearch').val(),
				ldapfilter: $('#addressbooks-ui-ldapfilter').val(),
				ldapbasednmodify: $('#addressbooks-ui-ldapbasednmodify').val(),
				ldapvcardconnector: $('#addressbooks-ui-ldapvcardconnector').val(),
				ldapvcardconnectorvalue: $('#addressbooks-ui-ldapvcardconnector-value').val(),
			}
		}
		)).then(function(response) {
			if(response.error) {
				error = response.message;
				if(typeof cb === 'function') {
					cb({error:true, message:error});
				}
				defer.reject(response);
			}
		OC.Contacts.otherBackendConfig.addressbookUiClose(divDlg);
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
	};

	OtherBackendConfig.prototype.addressbookUiClose = function(divDlg) {
		divDlg.ocdialog().ocdialog('close');
		divDlg.ocdialog().ocdialog('destroy').remove();
	};

	OtherBackendConfig.prototype.addressbookUiInit = function() {
		var self = this;
		
		$('#addressbooks-ui-ldapvcardconnector-value-p').hide();
		$('#addressbooks-ui-ldapvcardconnector-copyfrom-p').hide();
		$('#addressbooks-ui-name').change(function() {
			if ($('#addressbooks-ui-uri').val() === '') {
				$('#addressbooks-ui-uri').val($('#addressbooks-ui-name').val().toLowerCase().replace(' ', '-'));
			}
		});
		$('#addressbooks-ui-ldapanonymous').change(function() {
			if ($('#addressbooks-ui-ldapanonymous').prop('checked')) {
				$('#addressbooks-ui-ldapuser').prop('disabled', true);
				$('#addressbooks-ui-ldappass').prop('disabled', true);
			} else {
				$('#addressbooks-ui-ldapuser').removeProp('disabled');
				$('#addressbooks-ui-ldappass').removeProp('disabled');
			}
		});
		$('#addressbooks-ui-ldapreadonly').change(function() {
			if ($('#addressbooks-ui-ldapreadonly').prop('checked')) {
				$('#addressbooks-ui-ldapbasednmodify').prop('disabled', true);
			} else {
				$('#addressbooks-ui-ldapbasednmodify').removeProp('disabled');
			}
		});
		$('#addressbooks-ui-ldapbasednsearch').change(function() {
			if ($('#addressbooks-ui-ldapbasednmodify').val() == '') {
				$('#addressbooks-ui-ldapbasednmodify').val($('#addressbooks-ui-ldapbasednsearch').val());
			}
		});
		$('#addressbooks-ui-ldapbasednmodify').change(function() {
			if ($('#addressbooks-ui-ldapbasednsearch').val() == '') {
				$('#addressbooks-ui-ldapbasednsearch').val($('#addressbooks-ui-ldapbasednmodify').val());
			}
		});
		$.when(self.storage.getConnectors('ldap'))
		.then(function(response) {
			$('#addressbooks-ui-ldapvcardconnector').empty();
			var $option = null;
			for (var id = 0; id < response.data.length; id++) {
				if (response.data[id] != null) {
					$option = $('<option value="' + response.data[id].id + '">' + response.data[id].name + '</option>');
					$('#addressbooks-ui-ldapvcardconnector').append($option);
				}
			}
			$option = $('<option value="">' + 'Custom connector' + '</option>');
			$('#addressbooks-ui-ldapvcardconnector').append($option);
		})
		.fail(function(jqxhr, textStatus, error) {
			var err = textStatus + ', ' + error;
			console.log('Request Failed', + err);
			defer.reject({error:true, message:error});
		});
		$('#addressbooks-ui-ldapvcardconnector').change(function() {
			// Custom connector
			if ($('#addressbooks-ui-ldapvcardconnector').val() == '') {
				$('#addressbooks-ui-ldapvcardconnector-value-p').show();
				$('#addressbooks-ui-ldapvcardconnector-copyfrom-p').show();
				$.when(self.storage.getConnectors($('#addressbooks-ui-backend').val()))
				.then(function(response) {
					$('#addressbooks-ui-ldapvcardconnector-copyfrom').empty();
					var $option = $('<option value="">' + 'Select connector' + '</option>').attr('selected','selected');
					$('#addressbooks-ui-ldapvcardconnector-copyfrom').append($option);
					for (var id = 0; id < response.data.length; id++) {
						var $option = $('<option value="' + response.data[id].id + '">' + response.data[id].name + '</option>');
						$('#addressbooks-ui-ldapvcardconnector-copyfrom').append($option);
					}
				})
				.fail(function(jqxhr, textStatus, error) {
					var err = textStatus + ', ' + error;
					console.log('Request Failed', + err);
					defer.reject({error:true, message:error});
				});
			} else {
				$('#addressbooks-ui-ldapvcardconnector-value-p').hide();
				$('#addressbooks-ui-ldapvcardconnector-copyfrom-p').hide();
			}
		});
		$('#addressbooks-ui-ldapvcardconnector-copyfrom').change(function() {
			if ($('#addressbooks-ui-ldapvcardconnector-copyfrom').val() != '') {
				$.when(self.storage.getConnectors($('#addressbooks-ui-backend').val()))
				.then(function(response) {
					for (var id = 0; id < response.data.length; id++) {
						if ($('#addressbooks-ui-ldapvcardconnector-copyfrom').val() == response.data[id].id) {
							console.log(response.data[id].id);
							$('#addressbooks-ui-ldapvcardconnector-value').text(response.data[id].xml);
						}
					}
				})
				.fail(function(jqxhr, textStatus, error) {
					var err = textStatus + ', ' + error;
					console.log('Request Failed', + err);
					defer.reject({error:true, message:error});
				});
			}
		});
	};
  
})(window, jQuery, OC);
