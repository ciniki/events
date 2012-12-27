<?php
//
// Description
// -----------
// This function will return a list of events, with the dates formatted for english suffix.
//
// Arguments
// ---------
// ciniki:
// business_id:		The ID of the business to get events for.
// type:			The type of events to get.  Leave blank for upcoming, and 'past' to get past events.
// limit:			Limit the number of results, 0 for unlimited.
//
// Returns
// -------
// <events>
//		<event id="41" name="Event name" url="http://www.ciniki.org/" description="Event description" 
//			start_month="July" start_day="18th" start_year="2012"
//			end_month="July" end_day="20th" end_year="2012"
//			start_date="Jul 18, 2012" end_date="Jul 20, 2012" />
// </events>
//
function ciniki_events_web_list($ciniki, $business_id, $type, $limit) {

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
		. "";
	if( $type == 'past' ) {
		$strsql .= "AND ((ciniki_events.end_date > ciniki_events.start_date AND ciniki_events.end_date < DATE(NOW())) "
				. "OR (ciniki_events.end_date < ciniki_events.start_date AND ciniki_events.start_date <= DATE(NOW())) "
				. ") "
			. "ORDER BY ciniki_events.start_date DESC "
			. "";
	} else {
		$strsql .= "AND (ciniki_events.end_date >= DATE(NOW()) OR ciniki_events.start_date >= DATE(NOW())) "
			. "ORDER BY ciniki_events.start_date ASC "
			. "";
	}
	if( $limit != '' && $limit > 0 && is_int($limit) ) {
		$strsql .= "LIMIT $limit ";
	}

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbRspQuery');
	$rc = ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.events', 'events', 'event', array('stat'=>'ok', 'events'=>array()));
	
	return $rc;
}
?>
