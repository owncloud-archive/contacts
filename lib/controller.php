<?php
/**
 * @author Thomas Tanghus
 * Copyright (c) 2013 Thomas Tanghus (thomas@tanghus.net)
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Contacts;

use OCP\AppFramework\IApi,
	OC\AppFramework\Controller\Controller as  BaseController,
	OCP\IRequest,
	OCA\Contacts\App;

/**
 * Base Controller class for Contacts App
 */
class Controller extends BaseController {

	/**
	* @var Api
	*/
	protected $api;

	/**
	* @var IRequest
	*/
	protected $request;

	/**
	* @var App
	*/
	protected $app;

	public function __construct(IApi $api, IRequest $request, App $app) {
		$this->api = $api;
		$this->request = $request;
		$this->app = $app;
	}

}
