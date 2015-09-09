<?php

if (!defined('PHPUNIT_RUN')) {
	define('PHPUNIT_RUN', 1);
}

require_once __DIR__.'/../../../lib/base.php';

if(!class_exists('PHPUnit_Framework_TestCase')) {
	require_once('PHPUnit/Autoload.php');
}

OC_Hook::clear();
OCP\Util::connectHook('OCA\Contacts', 'pre_deleteContact', '\OCA\Contacts\Hooks', 'contactDeletion');
Sabre\VObject\Component\VCard::$componentMap['VCARD']	= '\OCA\Contacts\VObject\VCard';
Sabre\VObject\Component\VCard::$propertyMap['CATEGORIES'] = '\OCA\Contacts\VObject\GroupProperty';

if (version_compare(implode('.', \OCP\Util::getVersion()), '8.2', '>=')) {
 	\OC::$loader->addValidRoot(OC::$SERVERROOT . '/tests');
	\OC_App::loadApp('contacts');
}
 
