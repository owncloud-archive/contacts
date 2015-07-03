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

use OCP\AppFramework\App as MainApp;
use OCP\AppFramework\Http;
use OCP\AppFramework\IAppContainer;
use OCA\Contacts\Middleware\Http as HttpMiddleware;
use OCA\Contacts\Controller\PageController;
use OCA\Contacts\Controller\AddressBookController;
use OCA\Contacts\Controller\BackendController;
use OCA\Contacts\Controller\GroupController;
use OCA\Contacts\Controller\ContactController;
use OCA\Contacts\Controller\ContactPhotoController;
use OCA\Contacts\Controller\SettingsController;
use OCA\Contacts\Controller\ImportController;
use OCA\Contacts\Controller\ExportController;

/**
 * This class manages our app actions
 *
 * TODO: Build app properly on basis of AppFramework
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
		$this->server = $this->container->getServer();
		$user = \OC::$server->getUserSession()->getUser();
		if (is_null($user)) {
			\OC_Util::redirectToDefaultPage();
		}
		$userId = $user->getUID();
		$this->app = new App($userId);
		$this->registerServices();
		$this->container->registerMiddleware('HttpMiddleware');
	}

	public function registerServices() {
		$app = $this->app;
		$appName = $this->appName;

		$this->container->registerService('HttpMiddleware', function($container) {
			return new HttpMiddleware();
		});

		$this->container->registerService('PageController', function(IAppContainer $container) use($app, $appName) {
			$request = $container->query('Request');
			return new PageController($appName, $request);
		});
		$this->container->registerService('AddressBookController', function(IAppContainer $container) use($app, $appName) {
			$request = $container->query('Request');
			$userId = \OC::$server->getUserSession()->getUser()->getUID();
			return new AddressBookController($appName, $request, $app, $userId);
		});
		$this->container->registerService('BackendController', function(IAppContainer $container) use($app, $appName) {
			$request = $container->query('Request');
			return new BackendController($container, $request, $app);
		});
		$this->container->registerService('GroupController', function(IAppContainer $container) use($app, $appName) {
			$request = $container->query('Request');
			$tags = $container->getServer()->getTagManager()->load('contact', array(), true);
			return new GroupController($appName, $request, $app, $tags);
		});
		$this->container->registerService('ContactController', function(IAppContainer $container) use($app, $appName) {
			$request = $container->query('Request');
			return new ContactController($appName, $request, $app);
		});
		$this->container->registerService('ContactPhotoController', function(IAppContainer $container) use($app, $appName) {
			$request = $container->query('Request');
			$cache = $container->getServer()->getCache();
			return new ContactPhotoController($appName, $request, $app, $cache);
		});
		$this->container->registerService('SettingsController', function(IAppContainer $container) use($app, $appName) {
			$request = $container->query('Request');
			return new SettingsController($appName, $request, $app);
		});
		$this->container->registerService('ImportController', function(IAppContainer $container) use($app, $appName) {
			$request = $container->query('Request');
			$cache = $container->getServer()->getCache();
			$tags = $container->getServer()->getTagManager()->load('contact');
			return new ImportController($appName, $request, $app, $cache, $tags);
		});
		$this->container->registerService('ExportController', function(IAppContainer $container) use($app, $appName) {
			$request = $container->query('Request');
			return new ExportController($appName, $request, $app);
		});
	}

}
