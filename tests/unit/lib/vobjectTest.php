<?php
/**
 * Copyright (c) 2013 Thomas Tanghus (thomas@tanghus.net)
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

use Test\TestCase;

class Test_VObjects extends TestCase {

	public static function setUpBeforeClass() {
		\Sabre\VObject\Component\VCard::$propertyMap['CATEGORIES'] = 'OCA\Contacts\VObject\GroupProperty';
	}

	public function testCrappyVCard() {
		$cardData = file_get_contents(__DIR__ . '/../data/test3.vcf');
		$obj = \Sabre\VObject\Reader::read(
			$cardData,
			\Sabre\VObject\Reader::OPTION_IGNORE_INVALID_LINES
		);
		$obj->validate($obj::REPAIR);

		$this->assertEquals('2.1', (string)$obj->VERSION);
		$this->assertEquals('Adèle Fermée', (string)$obj->FN);
		$this->assertEquals('Fermée;Adèle;;;', (string)$obj->N);
	}

	public function testEscapedParameters() {
		$cardData = file_get_contents(__DIR__ . '/../data/test6.vcf');
		$obj = \Sabre\VObject\Reader::read(
			$cardData,
			\Sabre\VObject\Reader::OPTION_IGNORE_INVALID_LINES
		);
		$obj->validate($obj::REPAIR);

		$this->assertEquals('3.0', (string)$obj->VERSION);
		$this->assertEquals('Parameters;Escaped;;;', (string)$obj->N);
		$this->assertEquals('TEL;TYPE=PREF\,WORK\,VOICE:123456789' . "\r\n", $obj->TEL->serialize());
	}

	public function testGroupProperty() {
		$arr = array(
			'Home',
			'work',
			'Friends, Family',
		);

		$vcard = new \OCA\Contacts\VObject\VCard();

		$property = $vcard->createProperty('CATEGORIES');
		$property->setParts($arr);

		// Test parsing and serializing
		$this->assertEquals('Home,work,Friends\, Family', $property->getValue());
		$this->assertEquals('CATEGORIES:Home,work,Friends\, Family' . "\r\n", $property->serialize());
		$this->assertEquals(3, count($property->getParts()));

		// Test add
		$property->addGroup('Coworkers');
		$this->assertTrue($property->hasGroup('coworkers'));
		$this->assertEquals(4, count($property->getParts()));
		$this->assertEquals('Home,work,Friends\, Family,Coworkers', $property->getValue());

		// Test remove
		$this->assertTrue($property->hasGroup('Friends, fAmIlY'));
		$property->removeGroup('Friends, fAmIlY');
		$this->assertEquals(3, count($property->getParts()));
		$parts = $property->getParts();
		$this->assertEquals('Coworkers', $parts[2]);

		// Test rename
		$property->renameGroup('work', 'Work');
		$parts = $property->getParts();
		$this->assertEquals('Work', $parts[1]);
		//$this->assertEquals(true, false);
	}
}
