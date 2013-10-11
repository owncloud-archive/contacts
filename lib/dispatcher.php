<?php
/**
 * Copyright (c) 2013 Thomas Tanghus (thomas@tanghus.net)
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Contacts;

use OCP\AppFramework\App as MainApp,
	OCP\AppFramework\IAppContainer,
	OCA\Contacts\App,
	OCA\Contacts\Middleware\Http as HttpMiddleware,
	OCA\Contacts\Controller\AddressBookController,
	OCA\Contacts\Controller\GroupController,
	OCA\Contacts\Controller\ContactController,
	OCA\Contacts\Controller\ContactPhotoController,
	OCA\Contacts\Controller\SettingsController,
	OCA\Contacts\Controller\ImportController,
	OCA\Contacts\Controller\ExportController;

/**
 * This class manages our app actions
 */

class Dispatcher extends MainApp {
	/**
	* @var App
	*/
	protected $app;

	public function __construct($params) {
		parent::__construct('contacts');
		$this->container = $this->getContainer();
		$this->container['urlParams'] = $params;
		$this->container->registerMiddleware(new HttpMiddleware($this->container));
		// TODO: Remove this once sorted out.
		// When querying the middleware dispatcher Request gets instantiated
		// but urlParams isn't set yet
		//$this->container['urlParams'] = $params;
		//$this->middleware = $this->container->query('MiddlewareDispatcher');
		//$this->middleware->registerMiddleware(new HttpMiddleware($this->container->query('API')));
		//$this->api = $this->container->query('API');
		//$this->request = $this->container->query('Request');
		$this->app = new App($this->container->query('API')->getUserId());
		$this->registerServices();
	}

	public function registerServices() {
		$this->container->registerService('AddressBookController', function(IAppContainer $container) {
			return new AddressBookController($container, $this->app);
		});
		$this->container->registerService('GroupController', function(IAppContainer $container) {
			return new GroupController($container, $this->app);
		});
		$this->container->registerService('ContactController', function(IAppContainer $container) {
			return new ContactController($container, $this->app);
		});
		$this->container->registerService('ContactPhotoController', function(IAppContainer $container) {
			return new ContactPhotoController($container, $this->app);
		});
		$this->container->registerService('SettingsController', function(IAppContainer $container) {
			return new SettingsController($container, $this->app);
		});
		$this->container->registerService('ImportController', function(IAppContainer $container) {
			return new ImportController($container, $this->app);
		});
		$this->container->registerService('ExportController', function(IAppContainer $container) {
			return new ExportController($container, $this->app);
		});
	}

}