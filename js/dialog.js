
function openAddressbookUi() {
	$('#addressbooks-ui')[0].reset();
	$('#addressbooks-ui-uri').attr('disabled', false);
	$('#addressbooks-ui-ldapanonymous').attr('checked', false);
	$('#addressbooks-ui-ldapreadonly').attr('checked', false);
	$('#addressbooks-ui-ldapuser').attr('disabled', false);
	$('#addressbooks-ui-ldappass').attr('disabled', false);
	$('#addressbooks-ui-ldapbasednmodify').attr('disabled', false);

	$('#addressbooks-ui-backend').change(function() {
		storage = new OC.Contacts.Storage();
		addressbookUiInit();
		$.when(storage.getConnectors($('#addressbooks-ui-backend').val()))
		.then(function(response) {
			$('#addressbooks-ui-ldapvcardconnector').empty();
			for (id in response.data) {
				var $option = $('<option value="' + response.data[id]["id"] + '">' + response.data[id]["name"] + '</option>');
				$('#addressbooks-ui-ldapvcardconnector').append($option);
			}
			var $option = $('<option value="">' + 'Custom connector' + '</option>');
			$('#addressbooks-ui-ldapvcardconnector').append($option);
		})
		.fail(function(jqxhr, textStatus, error) {
			var err = textStatus + ', ' + error;
			console.log('Request Failed', + err);
			defer.reject({error:true, message:error});
		});
	});
	addressbookUiInit();
}

function editAddressbookUI(addressbook) {
	storage = new OC.Contacts.Storage();
	$('#addressbooks-ui-addressbookid').val(addressbook.id);
	$('#addressbooks-ui-backend option[value='+addressbook.backend+']').prop('selected', true);
	$('#addressbooks-ui-backend').prop('disabled', true);
	$('#addressbooks-ui-name').val(addressbook.displayname);
	if (addressbook.backend == 'ldap') {
		$('#addressbooks-ui-uri').val(addressbook.uri);
		$('#addressbooks-ui-description').val(addressbook.description);
		$('#addressbooks-ui-ldapurl').val(addressbook.ldapurl);
		$('#addressbooks-ui-ldapanonymous').attr('checked', (addressbook.ldapanonymous==true));
		$('#addressbooks-ui-ldapreadonly').attr('checked', (addressbook.ldapreadonly==true));
		$('#addressbooks-ui-ldapuser').val(addressbook.ldapuser);
		$('#addressbooks-ui-ldappass').val('nochange');
		$('#addressbooks-ui-ldappass-modified').val('false');
		$('#addressbooks-ui-ldappagesize').val(addressbook.ldappagesize);
		$('#addressbooks-ui-ldapbasednsearch').val(addressbook.ldapbasednsearch);
		$('#addressbooks-ui-ldapfilter').val(addressbook.ldapfilter);
		$('#addressbooks-ui-ldapbasednmodify').val(addressbook.ldapbasednmodify);
		$('#addressbooks-ui-uri').attr('disabled', true);
		if ($('#addressbooks-ui-ldapanonymous').prop('checked')) {
			$('#addressbooks-ui-ldapuser').attr('disabled', true);
			$('#addressbooks-ui-ldappass').attr('disabled', true);
		} else {
			$('#addressbooks-ui-ldapuser').removeAttr('disabled');
			$('#addressbooks-ui-ldappass').removeAttr('disabled');
		}
		if ($('#addressbooks-ui-ldapreadonly').prop('checked')) {
			$('#addressbooks-ui-ldapbasednmodify').attr('disabled', true);
		} else {
			$('#addressbooks-ui-ldapbasednmodify').removeAttr('disabled');
		}
		
		$('#addressbooks-ui-ldappass').change(function() {
			$('#addressbooks-ui-ldappass-modified').val('true');
		});

		$.when(storage.getConnectors($('#addressbooks-ui-backend').val()))
			.then(function(response) {
				$('#addressbooks-ui-ldapvcardconnector').empty();
				var custom = true;
				console.log('addressbook.ldapconnectorid', addressbook.ldapconnectorid);
				for (id in response.data) {
					console.log('response.data[id][\'id\']', response.data[id]['id']);
					if (response.data[id]['id'] == addressbook.ldapconnectorid) {
						var $option = $('<option value="' + response.data[id]['id'] + '">' + response.data[id]['name'] + '</option>').attr('selected','selected');
						custom = false;
					} else {
						var $option = $('<option value="' + response.data[id]['id'] + '">' + response.data[id]['name'] + '</option>');
					}
					$('#addressbooks-ui-ldapvcardconnector').append($option);
				}
				if (custom) {
					var $option = $('<option value="">' + 'Custom connector' + '</option>').attr('selected','selected');
					$('#addressbooks-ui-ldapvcardconnector-value-p').show();
					$('#addressbooks-ui-ldapvcardconnector-copyfrom-p').show();
					$.when(storage.getConnectors($('#addressbooks-ui-backend').val()))
					.then(function(response) {
						$('#addressbooks-ui-ldapvcardconnector-copyfrom').empty();
						var $option = $('<option value="">' + 'Select connector' + '</option>').attr('selected','selected');
						$('#addressbooks-ui-ldapvcardconnector-copyfrom').append($option);
						for (id in response.data) {
							var $option = $('<option value="' + response.data[id]['id'] + '">' + response.data[id]['name'] + '</option>');
							$('#addressbooks-ui-ldapvcardconnector-copyfrom').append($option);
						}
					})
					.fail(function(jqxhr, textStatus, error) {
						var err = textStatus + ', ' + error;
						console.log('Request Failed', + err);
						defer.reject({error:true, message:error});
					});
					$('#addressbooks-ui-ldapvcardconnector-copyfrom').change(function() {
						if ($('#addressbooks-ui-ldapvcardconnector-copyfrom').val() != '') {
							$.when(storage.getConnectors($('#addressbooks-ui-backend').val()))
							.then(function(response) {
								for (id in response.data) {
									if ($('#addressbooks-ui-ldapvcardconnector-copyfrom').val() == response.data[id]['id']) {
										console.log(response.data[id]['id']);
										$('#addressbooks-ui-ldapvcardconnector-value').text(response.data[id]['xml']);
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

					$('#addressbooks-ui-ldapvcardconnector-value').text(addressbook.ldap_vcard_connector);
				} else {
					var $option = $('<option value="">' + 'Custom connector' + '</option>');
				}
				$('#addressbooks-ui-ldapvcardconnector').append($option);
			})
			.fail(function(jqxhr, textStatus, error) {
				var err = textStatus + ', ' + error;
				console.log('Request Failed', + err);
				defer.reject({error:true, message:error});
			});
	}
	addressbookUiInit();
}

function addressbookUiOk() {
	storage = new OC.Contacts.Storage();
	//addressbook = new OC.Contacts.AddressBook();
	var defer = $.Deferred();

	$.when(storage.addAddressBook($('#addressbooks-ui-backend').val(),
	{
		displayname: $('#addressbooks-ui-name').val(),
		description: $('#addressbooks-ui-description').val(),
		uri: ($('#addressbooks-ui-uri').val()=='')?$('#addressbooks-ui-name').val():$('#addressbooks-ui-uri').val(),
		ldapurl: $('#addressbooks-ui-ldapurl').val(),
		ldapanonymous: $('#addressbooks-ui-ldapanonymous').prop('checked')==true?'true':'false',
		ldapreadonly: $('#addressbooks-ui-ldapreadonly').prop('checked')==true?'true':'false',
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
			var book = OC.Contacts.addressBooks.insertAddressBook(response.data);
			$(document).trigger('status.addressbook.added');
			if(typeof cb === 'function') {
				cb({error:false, addressbook: book});
			}
			defer.resolve({error:false, addressbook: book});
			$("#addressbooks-ui").dialog('close');
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
}

function addressbookUiEditOk() {
	storage = new OC.Contacts.Storage();
	//addressbook = new OC.Contacts.Addressbook();
	var defer = $.Deferred();
	console.log($("#addressbooks-ui-addressbookid").val())

	$.when(storage.updateAddressBook($('#addressbooks-ui-backend').val(), $('#addressbooks-ui-addressbookid').val(),
	{
		displayname: $('#addressbooks-ui-name').val(),
		description: $('#addressbooks-ui-description').val(),
		uri: $('#addressbooks-ui-uri').val(),
		ldapurl: $('#addressbooks-ui-ldapurl').val(),
		ldapanonymous: $('#addressbooks-ui-ldapanonymous').prop('checked')==true?'true':'false',
		ldapreadonly: $('#addressbooks-ui-ldapreadonly').prop('checked')==true?'true':'false',
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
	)).then(function(response) {
		if(response.error) {
			error = response.message;
			if(typeof cb === 'function') {
				cb({error:true, message:error});
			}
			defer.reject(response);
		} else {
			/*var book = addressbook.insertAddressBook(response.data);
			$(document).trigger('status.addressbook.added');
			if(typeof cb === 'function') {
				cb({error:false, addressbook: book});
			}
			defer.resolve({error:false, addressbook: book});*/
			$("#addressbooks-ui").dialog('close');
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
}

function addressbookUiCancel() {
	$('#addressbooks-ui').dialog('close');
}

function addressbookUiInit() {
	storage = new OC.Contacts.Storage();
	
	if ($('#addressbooks-ui-backend').val() == 'local') {
		$('#addressbooks-ui-uri-p').hide();
		$('#addressbooks-ui-description-p').hide();
		$('#addressbooks-ui-ldapurl-p').hide();
		$('#addressbooks-ui-ldapanonymous-p').hide();
		$('#addressbooks-ui-ldapreadonly-p').hide();
		$('#addressbooks-ui-ldapuser-p').hide();
		$('#addressbooks-ui-ldappass-p').hide();
		$('#addressbooks-ui-ldappagesize-p').hide();
		$('#addressbooks-ui-ldapbasednsearch-p').hide();
		$('#addressbooks-ui-ldapfilter-p').hide();
		$('#addressbooks-ui-ldapbasednmodify-p').hide();
		$('#addressbooks-ui-ldapvcardconnector-p').hide();
		$('#addressbooks-ui-ldapvcardconnector-value-p').hide();
		$('#addressbooks-ui-ldapvcardconnector-copyfrom-p').hide();
	} else if ($('#addressbooks-ui-backend').val() == 'ldap') {
		$('#addressbooks-ui-uri-p').show();
		$('#addressbooks-ui-description-p').show();
		$('#addressbooks-ui-ldapurl-p').show();
		$('#addressbooks-ui-ldapanonymous-p').show();
		$('#addressbooks-ui-ldapreadonly-p').show();
		$('#addressbooks-ui-ldapuser-p').show();
		$('#addressbooks-ui-ldappass-p').show();
		$('#addressbooks-ui-ldappagesize-p').show();
		$('#addressbooks-ui-ldapbasednsearch-p').show();
		$('#addressbooks-ui-ldapfilter-p').show();
		$('#addressbooks-ui-ldapbasednmodify-p').show();
		$('#addressbooks-ui-ldapvcardconnector-p').show();
		$('#addressbooks-ui-ldapvcardconnector-value-p').hide();
		$('#addressbooks-ui-ldapvcardconnector-copyfrom-p').hide();
	}
	$('#addressbooks-ui-name').change(function() {
		if ($('#addressbooks-ui-uri').val() == '') {
			$('#addressbooks-ui-uri').val($('#addressbooks-ui-name').val().toLowerCase().replace(' ', '-'));
		}
	});
	$('#addressbooks-ui-ldapanonymous').change(function() {
		if ($('#addressbooks-ui-ldapanonymous').prop('checked')) {
			$('#addressbooks-ui-ldapuser').attr('disabled', true);
			$('#addressbooks-ui-ldappass').attr('disabled', true);
		} else {
			$('#addressbooks-ui-ldapuser').removeAttr('disabled');
			$('#addressbooks-ui-ldappass').removeAttr('disabled');
		}
	});
	$('#addressbooks-ui-ldapreadonly').change(function() {
		if ($('#addressbooks-ui-ldapreadonly').prop('checked')) {
			$('#addressbooks-ui-ldapbasednmodify').attr('disabled', true);
		} else {
			$('#addressbooks-ui-ldapbasednmodify').removeAttr('disabled');
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
	$('#addressbooks-ui-ldapvcardconnector').change(function() {
		// Custom connector
		if ($('#addressbooks-ui-ldapvcardconnector').val() == '') {
			$('#addressbooks-ui-ldapvcardconnector-value-p').show();
			$('#addressbooks-ui-ldapvcardconnector-value').text('');
			$('#addressbooks-ui-ldapvcardconnector-copyfrom-p').show();
			$.when(storage.getConnectors($('#addressbooks-ui-backend').val()))
			.then(function(response) {
				$('#addressbooks-ui-ldapvcardconnector-copyfrom').empty();
				var $option = $('<option value="">' + 'Select connector' + '</option>').attr('selected','selected');
				$('#addressbooks-ui-ldapvcardconnector-copyfrom').append($option);
				for (id in response.data) {
					var $option = $('<option value="' + response.data[id]['id'] + '">' + response.data[id]['name'] + '</option>');
					$('#addressbooks-ui-ldapvcardconnector-copyfrom').append($option);
				}
			})
			.fail(function(jqxhr, textStatus, error) {
				var err = textStatus + ', ' + error;
				console.log('Request Failed', + err);
				defer.reject({error:true, message:error});
			});
			$('#addressbooks-ui-ldapvcardconnector-copyfrom').change(function() {
				if ($('#addressbooks-ui-ldapvcardconnector-copyfrom').val() != '') {
					$.when(storage.getConnectors($('#addressbooks-ui-backend').val()))
					.then(function(response) {
						for (id in response.data) {
							if ($('#addressbooks-ui-ldapvcardconnector-copyfrom').val() == response.data[id]['id']) {
								console.log(response.data[id]['id']);
								$('#addressbooks-ui-ldapvcardconnector-value').text(response.data[id]['xml']);
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
		}
	});
}
