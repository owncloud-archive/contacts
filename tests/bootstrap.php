<?php

if(!defined('PHPUNIT_RUN')) {
	define('PHPUNIT_RUN', 1);
}
require_once __DIR__.'/../../../lib/base.php';

if(!class_exists('PHPUnit_Framework_TestCase')) {
	require_once('PHPUnit/Autoload.php');
}

include_once('lib/testcase.php');

OC_Hook::clear();
\OCP\Util::connectHook('OCA\Contacts', 'pre_deleteContact', '\OCA\Contacts\Hooks', 'contactDeletion');

\Sabre\VObject\Component\VCard::$componentMap['VCARD']	= '\OCA\Contacts\VObject\VCard';
\Sabre\VObject\Component\VCard::$propertyMap['CATEGORIES'] = '\OCA\Contacts\VObject\GroupProperty';

