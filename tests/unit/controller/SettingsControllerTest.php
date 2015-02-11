<?php
/**
 * @author Lukas Reschke
 * @copyright 2015 Lukas Reschke lukas@owncloud.com
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
namespace OCA\Contacts\Controller;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use Test\TestCase;
use OCP\IRequest;
use OCP\IConfig;

/**
 * Class SettingsControllerTest
 *
 * @package OCA\Contacts\Controller
 */
class SettingsControllerTest extends TestCase {
	/** @var IRequest **/
	private $request;
	/** @var string */
	private $appName;
	/** @var SettingsController */
	private $controller;
	/** @var IConfig */
	private $config;
	/** @var string */
	private $userId;

	public function setUp (){
		$this->request = $this->getMockBuilder('OCP\IRequest')
			->disableOriginalConstructor()
			->getMock();
		$this->config = $this->getMockBuilder('\OCP\IConfig')
			->disableOriginalConstructor()
			->getMock();
		$this->userId = 'JohnDoe';

		$this->appName = 'contacts';
		$this->controller = new SettingsController(
			$this->appName,
			$this->request,
			$this->config,
			$this->userId
		);
	}

	public function testSetWithMissingValues() {
		$expected = new JSONResponse();
		$expected->setStatus(Http::STATUS_PRECONDITION_FAILED);

		$this->assertEquals($expected, $this->controller->set());
		$this->assertEquals($expected, $this->controller->set('key'));
		$this->assertEquals($expected, $this->controller->set('', 'value'));
	}

	public function testSetWorking() {
		$this->config->expects($this->once())
			->method('setUserValue')
			->with('JohnDoe', 'contacts', 'keyValue', 'valueValue');

		$expected = new JSONResponse();
		$expected->setData(['key' => 'keyValue', 'value' => 'valueValue']);

		$this->assertEquals($expected, $this->controller->set('keyValue', 'valueValue'));
	}

	public function testSetException() {
		$this->config->expects($this->once())
			->method('setUserValue')
			->with('JohnDoe', 'contacts', 'keyValue', 'valueValue')
			->will($this->throwException(new \Exception()));

		$expected = new JSONResponse();
		$expected->setStatus(Http::STATUS_INTERNAL_SERVER_ERROR);

		$this->assertEquals($expected, $this->controller->set('keyValue', 'valueValue'));
	}
}
