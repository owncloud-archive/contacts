<?php
/**
 * Copyright (c) 2015 Vincent Petry <pvince81@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Contacts;

class TestCase extends \PHPUnit_Framework_TestCase {

	/**
	 * @var string
	 */
	protected $testUser;

	public function setUp() {
		$this->testUser = uniqid('user_');

		// needed because some parts of code call "getRequest()" and "getSession()"
		$session = $this->getMock('\OC\Session\Memory', array(), array(''));
		$session->expects($this->any())
			->method('get')
			->with('user_id')
			->will($this->returnValue($this->testUser));
		$userObject = $this->getMock('\OCP\IUser');
		$userObject->expects($this->any())
			->method('getUId')
			->will($this->returnValue($this->testUser));

		$userSession = $this->getMockBuilder('\OC\User\Session')
			->disableOriginalConstructor()
			->getMock(); 

		$userSession->expects($this->any())
			->method('getUser')
			->will($this->returnValue($userObject));
		$userSession->expects($this->any())
			->method('getSession')
			->will($this->returnValue($session));
		\OC::$server->registerService('UserSession', function (\OCP\IServerContainer $c) use ($userSession){
			return $userSession;
		});
	}
}
