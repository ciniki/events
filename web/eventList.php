<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_events_web_eventList($ciniki, $settings, $business_id, $type, $limit) {

	$strsql = "SELECT ciniki_events.id, "
		. "ciniki_events.name, "
		. "ciniki_events.permalink, "
		. "ciniki_events.url, "
		. "IF(ciniki_events.long_description='', 'no', 'yes') AS isdetails, "
		. "DATE_FORMAT(start_date, '%M') AS start_month, "
		. "DATE_FORMAT(start_date, '%D') AS start_day, "
		. "DATE_FORMAT(start_date, '%Y') AS start_year, "
		. "IF(end_date = '0000-00-00', '', DATE_FORMAT(end_date, '%M')) AS end_month, "
		. "IF(end_date = '0000-00-00', '', DATE_FORMAT(end_date, '%D')) AS end_day, "
		. "IF(end_date = '0000-00-00', '', DATE_FORMAT(end_date, '%Y')) AS end_year, "
		. "DATE_FORMAT(start_date, '%a %b %c, %Y') AS start_date, "
		. "DATE_FORMAT(end_date, '%a %b %c, %Y') AS end_date, "
		. "ciniki_events.description, "
		. "ciniki_events.primary_image_id "
		. "FROM ciniki_events "
		. "WHERE ciniki_events.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' ";
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

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.events', array(
		array('container'=>'events', 'fname'=>'id', 
			'fields'=>array('id', 'name', 'image_id'=>'primary_image_id', 'isdetails', 
				'start_month', 'start_day', 'start_year', 'end_month', 'end_day', 'end_year', 'start_date', 'end_date', 
				'permalink', 'description', 'url')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['events']) ) {
		return array('stat'=>'ok', 'events'=>array());
	}
	return array('stat'=>'ok', 'events'=>$rc['events']);
}
?>
