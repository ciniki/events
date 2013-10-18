<?php
//
// Description
// -----------
// This method will return the list of events for a business.  It is restricted
// to business owners and sysadmins.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business to get events for.
//
// Returns
// -------
// <upcoming>
//		<event id="41" name="Event name" url="http://www.ciniki.org/" description="Event description" start_date="Jul 18, 2012" end_date="Jul 20, 2012" />
// </upcoming>
// <past />
//
function ciniki_events_eventList($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
    //  
    // Check access to business_id as owner, or sys admin. 
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'events', 'private', 'checkAccess');
    $ac = ciniki_events_checkAccess($ciniki, $args['business_id'], 'ciniki.events.eventList');
    if( $ac['stat'] != 'ok' ) { 
        return $ac;
    }   

	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki);
	
	//
	// Load the upcoming events
	//
	$strsql = "SELECT id, name, url, description, "
		. "DATE_FORMAT(start_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS start_date, "
		. "DATE_FORMAT(end_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS end_date "
		. "FROM ciniki_events "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND (end_date >= DATE(NOW()) OR start_date >= DATE(NOW())) "
		. "ORDER BY ciniki_events.start_date ASC "
		. "";

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbRspQuery');
	$rc = ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.events', 'events', 'event', array('stat'=>'ok', 'events'=>array()));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	
	$rsp = array('stat'=>'ok', 'upcoming'=>$rc['events']);

	//
	// Load the past events
	//
	$strsql = "SELECT id, name, url, description, "
		. "DATE_FORMAT(start_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS start_date, "
		. "DATE_FORMAT(end_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS end_date "
		. "FROM ciniki_events "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ((ciniki_events.end_date > ciniki_events.start_date AND ciniki_events.end_date < DATE(NOW())) "
			. "OR (ciniki_events.end_date < ciniki_events.start_date AND ciniki_events.start_date <= DATE(NOW())) "
			. ") "
		. "ORDER BY ciniki_events.start_date ASC "
		. "";

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbRspQuery');
	$rc = ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.events', 'events', 'event', array('stat'=>'ok', 'events'=>array()));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$rsp['past'] = $rc['events'];

	return $rsp;
}
?>