/*
 * Copyright (c) 2015
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */
(function() {

	/**
	 * Construct a new FileActions instance
	 * @constructs Files
	 */
	var Contacts = function() {
		this.initialize();
	};
	/**
	 * @memberof OCA.Search
	 */
	Contacts.prototype = {

		fileList: null,

		/**
		 * Initialize the file search
		 */
		initialize: function() {

			this.renderResult = function($row, result) {
				if (!$row) {
					return $row;
				}
				if (result.url !== null) {
					$row.find('td.icon').css('background-image', 'url(' + result.url + ')');
				} else {
					$row.find('td.icon')
						.addClass('avatar')
						.css('height', '32px')
						.imageplaceholder(result.name);
				}
				return $row;
			};

			this.setFileList = function (fileList) {
				this.fileList = fileList;
			};

			OC.Plugins.register('OCA.Search', this);
		},
		attach: function(search) {
			search.setRenderer('contact', this.renderResult.bind(this));
		}
	};
	OCA.Search.Contacts = Contacts;
	OCA.Search.contacts = new Contacts();
})();
