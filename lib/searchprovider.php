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

class SearchProvider extends \OC_Search_Provider{
	function search($query) {
		$unescape = function($value) {
			return strtr($value, array('\,' => ',', '\;' => ';'));
		};

		$app = new App();
		$searchresults = array(	);
		$results = \OCP\Contacts::search($query, array('N', 'FN', 'EMAIL', 'NICKNAME', 'ORG'));
		$l = new \OC_l10n('contacts');
		foreach($results as $result) {
			$link = \OCP\Util::linkToRoute('contacts_index').'#' . $result['id'];
			$props = array();
			foreach(array('EMAIL', 'NICKNAME', 'ORG') as $searchvar) {
				if(isset($result[$searchvar]) && count($result[$searchvar]) > 0 && strlen($result[$searchvar][0]) > 3) {
					$props = array_merge($props, $result[$searchvar]);
				}
			}
			$props = array_map($unescape, $props);
			$searchresults[]=new \OC_Search_Result($result['FN'], implode(', ', $props), $link, (string)$l->t('Contact'));//$name,$text,$link,$type
		}
		return $searchresults;
	}
}
