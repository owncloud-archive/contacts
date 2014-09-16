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
	public function search($query) {

		$searchresults = array();
		$results = \OCP\Contacts::search($query, array('N', 'FN', 'EMAIL', 'NICKNAME', 'ORG'));
		$l = new \OC_l10n('contacts');
		foreach($results as $result) {
			$link = \OCP\Util::linkToRoute('contacts_index').'#' . $result['id'];

			$get = function($k) use($result) {
				if (isset($result[$k]) && ($v = $result[$k])) {
					if (is_array($v)) {
						$v = implode(',', array_filter($v));
					}
					return trim($v);
				};
				return null;
			};

			$display = $get('N') ?: $get('FN') ?: $get('NICKNAME') ?: $get('EMAIL');
			$searchresults[]=new \OC_Search_Result($result['id'], $display, $link, (string)$l->t('Contact'));
		}
		return $searchresults;
	}
}
