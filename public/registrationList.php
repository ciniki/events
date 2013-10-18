<?php
//
// Description
// -----------
// This method will return the list of customers who have registered for an event.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business to get events for.
//
// Returns
// -------
//
function ciniki_events_registrationList($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'event_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Event'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
    //  
    // Check access to business_id as owner, or sys admin. 
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'events', 'private', 'checkAccess');
    $ac = ciniki_events_checkAccess($ciniki, $args['business_id'], 'ciniki.events.registrationList');
    if( $ac['stat'] != 'ok' ) { 
        return $ac;
    }   
	$modules = $ac['modules'];

	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki);

	//
	// Build the query string to get the list of registrations
	//
	$strsql = "SELECT ciniki_event_registrations.id, "
		. "ciniki_event_registrations.customer_id, "
		. "CONCAT_WS(' ', ciniki_customers.first, ciniki_customers.last) AS customer_name, "
		. "ciniki_event_registrations.num_tickets "
		. "FROM ciniki_event_registrations "
		. "LEFT JOIN ciniki_customers ON (ciniki_event_registrations.customer_id = ciniki_customers.id "
			. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "') "
		. "WHERE ciniki_event_registrations.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_event_registrations.event_id = '" . ciniki_core_dbQuote($ciniki, $args['event_id']) . "' "
		. "ORDER BY ciniki_customers.last, ciniki_customers.first "
		. "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.events', array(
		array('container'=>'registrations', 'fname'=>'id', 'name'=>'registration',
			'fields'=>array('id', 'customer_id', 'customer_name', 'num_tickets')),
		));
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1364', 'msg'=>'Unable to get the list of registrations', 'err'=>$rc['err']));
	}
	if( !isset($rc['registrations']) ) {
		return array('stat'=>'ok', 'registrations'=>array());
	}

	//
	// FIXME: check for status of invoices
	//
	// if( isset($modules['ciniki.pos']) ) {
	// }

	return array('stat'=>'ok', 'registrations'=>$rc['registrations']);
}
?>
