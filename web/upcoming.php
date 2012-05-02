<?php
//
// Description
// -----------
// This function will return a list of events, with the dates formatted for english suffix
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business to get events for.
//
// Returns
// -------
// <events>
// 	<event id="" name="" />
// </events>
//
function ciniki_events_webUpcoming($ciniki, $business_id) {

	$strsql = "SELECT id, name, url, description, "
		. "DATE_FORMAT(start_date, '%M') AS start_month, "
		. "DATE_FORMAT(start_date, '%D') AS start_day, "
		. "DATE_FORMAT(start_date, '%Y') AS start_year, "
		. "IF(end_date = '0000-00-00', '', DATE_FORMAT(end_date, '%M')) AS end_month, "
		. "IF(end_date = '0000-00-00', '', DATE_FORMAT(end_date, '%D')) AS end_day, "
		. "IF(end_date = '0000-00-00', '', DATE_FORMAT(end_date, '%Y')) AS end_year, "
		. "DATE_FORMAT(start_date, '%b %c, %Y') AS start_date, "
		. "DATE_FORMAT(end_date, '%b %c, %Y') AS end_date "
		. "FROM ciniki_events "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND (ciniki_events.end_date >= DATE(NOW()) OR ciniki_events.start_date >= DATE(NOW())) "
		. "ORDER BY ciniki_events.start_date ASC "
		. "";

    require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbRspQuery.php');
	return ciniki_core_dbRspQuery($ciniki, $strsql, 'events', 'events', 'event', array('stat'=>'ok', 'events'=>array()));
}
?>
