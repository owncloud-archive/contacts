<?php
/**
 * @author Thomas Tanghus
 * @copyright 2013-2014 Thomas Tanghus (thomas@tanghus.net)
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Contacts;

use OCP\AppFramework\App as MainApp,
	OCP\AppFramework\IAppContainer,
	OCA\Contacts\App,
	OCA\Contacts\Middleware\Http as HttpMiddleware,
	OCA\Contacts\Controller\PageController,
	OCA\Contacts\Controller\AddressBookController,
	OCA\Contacts\Controller\BackendController,
	OCA\Contacts\Controller\GroupController,
	OCA\Contacts\Controller\ContactController,
	OCA\Contacts\Controller\ContactPhotoController,
	OCA\Contacts\Controller\SettingsController,
	OCA\Contacts\Controller\ImportController,
	OCA\Contacts\Controller\ExportController;

/**
 * This class manages our app actions
 *
 * TODO: Merge with App
 */

class Dispatcher extends MainApp {

	/**
	 * @var string
	 */
	protected $appName;

	/**
	* @var \OCA\Contacts\App
	*/
	protected $app;

	/**
	* @var \OCP\IServerContainer
	*/
	protected $server;

	/**
	* @var \OCP\AppFramework\IAppContainer
	*/
	protected $container;

	public function __construct($params) {
		$this->appName = 'contacts';
		parent::__construct($this->appName, $params);
		$this->container = $this->getContainer();
		$this->container->registerMiddleware(new HttpMiddleware());
		$this->server = $this->container->getServer();
		$this->app = new App($this->container->query('API')->getUserId());
		$this->registerServices();
	}

	public function registerServices() {
		$app = $this->app;

		$this->container->registerService('PageController', function(IAppContainer $container) use($app) {
			$request = $container->query('Request');
			return new PageController($this->appName, $request);
		});
		$this->container->registerService('AddressBookController', function(IAppContainer $container) use($app) {
			$request = $container->query('Request');
			return new AddressBookController($this->appName, $request, $app);
		});
		$this->container->registerService('BackendController', function(IAppContainer $container) use($app) {
			return new BackendController($container, $request, $app);
		});
		$this->container->registerService('GroupController', function(IAppContainer $container) use($app) {
			$request = $container->query('Request');
			$tags = $this->server->getTagManager()->load('contact');
			return new GroupController($this->appName, $request, $app, $tags);
		});
		$this->container->registerService('ContactController', function(IAppContainer $container) use($app) {
			$request = $container->query('Request');
			return new ContactController($this->appName, $request, $app);
		});
		$this->container->registerService('ContactPhotoController', function(IAppContainer $container) use($app) {
			$request = $container->query('Request');
			$cache = $this->server->getCache();
			return new ContactPhotoController($this->appName, $request, $app, $cache);
		});
		$this->container->registerService('SettingsController', function(IAppContainer $container) use($app) {
			$request = $container->query('Request');
			return new SettingsController($this->appName, $request, $app);
		});
		$this->container->registerService('ImportController', function(IAppContainer $container) use($app) {
			$request = $container->query('Request');
			$cache = $this->server->getCache();
			return new ImportController($this->appName, $request, $app, $cache);
		});
		$this->container->registerService('ExportController', function(IAppContainer $container) use($app) {
			$request = $container->query('Request');
			return new ExportController($this->appName, $request, $app);
		});
	}

}
