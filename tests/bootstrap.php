<?php

global $RUNTIME_NOAPPS;
$RUNTIME_NOAPPS = true;

define('PHPUNIT_RUN', 1);

require_once __DIR__ . '/../3rdparty/vendor/autoload.php';
require_once __DIR__.'/../../core/lib/base.php';

//\Sabre\VObject\Component\VCard::$componentMap['VCARD']	= '\OCA\Contacts\VObject\VCard';


if(!class_exists('PHPUnit_Framework_TestCase')) {
	require_once('PHPUnit/Autoload.php');
}

OC_App::enable('contacts');

OC_Hook::clear();
OC_Log::$enabled = true;
