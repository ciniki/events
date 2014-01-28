<?php
//
// Description
// ===========
// This method will return all the information about an event registration.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business the event is attached to.
// registration_id:		The ID of the registration to get the details for.
// 
// Returns
// -------
//
function ciniki_events_registrationGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'registration_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Registration'), 
		'customer'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customer'),
		'invoice'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Invoice'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'events', 'private', 'checkAccess');
    $rc = ciniki_events_checkAccess($ciniki, $args['business_id'], 'ciniki.events.registrationGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki);

	$strsql = "SELECT ciniki_event_registrations.id, "
		. "ciniki_event_registrations.event_id, "
		. "ciniki_event_registrations.customer_id, "
		. "ciniki_event_registrations.invoice_id, "
		. "ciniki_event_registrations.num_tickets, "
		. "ciniki_event_registrations.customer_notes, "
		. "ciniki_event_registrations.notes "
		. "FROM ciniki_event_registrations "
		. "WHERE ciniki_event_registrations.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_event_registrations.id = '" . ciniki_core_dbQuote($ciniki, $args['registration_id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.events', array(
		array('container'=>'registrations', 'fname'=>'id', 'name'=>'registration',
			'fields'=>array('id', 'event_id', 'customer_id', 'invoice_id', 'num_tickets', 'customer_notes', 'notes')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['registrations']) || !isset($rc['registrations'][0]) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1329', 'msg'=>'Unable to find registration'));
	}
	$registration = $rc['registrations'][0]['registration'];

	//
	// If include customer information
	//
	if( isset($args['customer']) && $args['customer'] == 'yes' && $registration['customer_id'] > 0 ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'customerDetails');
		$rc = ciniki_customers__customerDetails($ciniki, $args['business_id'], $registration['customer_id'], 
			array('emails'=>'yes', 'addresses'=>'yes', 'subscriptions'=>'no'));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$registration['customer'] = $rc['details'];
	}

	//
	// Add invoice information
	//
	if( isset($args['invoice']) && $args['invoice'] == 'yes' && $registration['invoice_id'] > 0 ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceLoad');
		$rc = ciniki_sapos_invoiceLoad($ciniki, $args['business_id'], $registration['invoice_id']);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$registration['invoice'] = $rc['invoice'];
	}

	return array('stat'=>'ok', 'registration'=>$registration);
}
?>
