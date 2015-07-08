<?php
/**
 * @author Lukas Reschke
 * @copyright 2015 Lukas Reschke lukas@owncloud.com
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
namespace OCA\Contacts\Controller;

use OCP\AppFramework\Http\TemplateResponse;
use Test\TestCase;
use OCP\IRequest;
use OCA\Contacts\ImportManager;
use OCA\Contacts\Factory\UtilFactory;

/**
 * Class PageControllerTest
 *
 * @package OCA\Contacts\Controller
 */
class PageControllerTest extends TestCase {
	/** @var IRequest **/
	private $request;
	/** @var string */
	private $appName;
	/** @var PageController */
	private $controller;
	/** @var ImportManager */
	private $importManager;
	/** @var UtilFactory */
	private $utilFactory;

	public function setUp (){
		$this->request = $this->getMockBuilder('OCP\IRequest')
			->disableOriginalConstructor()
			->getMock();
		$this->importManager = $this->getMockBuilder('\OCA\Contacts\ImportManager')
			->disableOriginalConstructor()
			->getMock();
		$this->utilFactory = $this->getMockBuilder('\OCA\Contacts\Factory\UtilFactory')
			->disableOriginalConstructor()
			->getMock();

		$this->appName = 'contacts';
		$this->controller = new PageController(
			$this->appName,
			$this->request,
			$this->importManager,
			$this->utilFactory
		);
	}

	public function testIndex() {
		$expected = new TemplateResponse($this->appName, 'contacts');
		$expected->setParams([
			'uploadMaxFilesize' => null,
			'uploadMaxHumanFilesize' => null,
			'phoneTypes' => [
				'HOME' => 'Home',
				'CELL' => 'Mobile',
				'WORK' => 'Work',
				'TEXT' => 'Text',
				'VOICE' => 'Voice',
				'MSG' => 'Message',
				'FAX' => 'Fax',
				'VIDEO' => 'Video',
				'PAGER' => 'Pager',
				'OTHER' => 'Other',
			],
			'emailTypes' => [
				'WORK' => 'Work',
				'HOME' => 'Home',
				'INTERNET' => 'Internet',
				'OTHER' => 'Other',
			],
			'adrTypes' => [
				'WORK' => 'Work',
				'HOME' => 'Home',
				'OTHER' => 'Other',
			],
			'imppTypes' => [
				'WORK' => 'Work',
				'HOME' => 'Home',
				'OTHER' => 'Other',
			],
			'imProtocols' => [
				'jabber' => 'Jabber',
				'sip' => 'Internet call',
				'aim' => 'AIM',
				'msn' => 'MSN',
				'twitter' => 'Twitter',
				'googletalk' => 'GoogleTalk',
				'facebook' => 'Facebook',
				'xmpp' => 'XMPP',
				'icq' => 'ICQ',
				'yahoo' => 'Yahoo',
				'skype' => 'Skype',
				'qq' => 'QQ',
				'gadugadu' => 'GaduGadu',
				'owncloud-handle' => 'ownCloud',
			],
			'importManager' => $this->importManager,
			'cloudTypes' => [
				'HOME' => 'Home',
				'WORK' => 'Work',
				'OTHER' => 'Other',
			],
		]);

		$this->assertEquals($expected, $this->controller->index());
	}

}
