<?php
/**
 * Copyright (c) 2014, Tobia De Koninck hey--at--ledfan.be
 * This file is licensed under the AGPL version 3 or later.
 * See the COPYING file.
 */

if(!function_exists('vendor_script')){
	function vendor_script($app, $files){
		if(is_array($files)) {
			foreach($files as $file) {
				\OCP\Util::addScript('contacts', '../vendor/' . $file);
			}
		} else {
			\OCP\Util::addScript('contacts', '../vendor/' . $files);
		}
	}
}

if(!function_exists('vendor_style')){
	function vendor_style($app, $files){
		if(is_array($files)) {
			foreach($files as $file) {
				\OCP\Util::addStyle('contacts', '../vendor/' . $file);
			}
		} else {
			\OCP\Util::addStyle('contacts', '../vendor/' . $files);
		}
	}
}

