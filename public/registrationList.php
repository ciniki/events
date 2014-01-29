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
	if( isset($modules['ciniki.sapos']) ) {
		//
		// Load the status maps for the text description of each status
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceStatusMaps');
		$rc = ciniki_sapos_invoiceStatusMaps($ciniki);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$status_maps = $rc['maps'];
		$status_maps[0] = 'No Invoice';

		$strsql = "SELECT ciniki_event_registrations.id, "
			. "ciniki_event_registrations.customer_id, "
			. "IFNULL(ciniki_customers.display_name, '') AS customer_name, "
			. "ciniki_event_registrations.num_tickets, "
			. "ciniki_event_registrations.invoice_id, "
			. "ciniki_sapos_invoices.status AS invoice_status, "
			. "IFNULL(ciniki_sapos_invoices.status, 0) AS invoice_status_text "
			. "FROM ciniki_event_registrations "
			. "LEFT JOIN ciniki_customers ON (ciniki_event_registrations.customer_id = ciniki_customers.id "
				. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. ") "
			. "LEFT JOIN ciniki_sapos_invoices ON (ciniki_event_registrations.invoice_id = ciniki_sapos_invoices.id "
				. "AND ciniki_sapos_invoices.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. ") "
			. "WHERE ciniki_event_registrations.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_event_registrations.event_id = '" . ciniki_core_dbQuote($ciniki, $args['event_id']) . "' "
			. "ORDER BY ciniki_customers.last, ciniki_customers.first "
			. "";
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.events', array(
			array('container'=>'registrations', 'fname'=>'id', 'name'=>'registration',
				'fields'=>array('id', 'customer_id', 'customer_name', 'num_tickets', 
					'invoice_id', 'invoice_status', 'invoice_status_text'),
				'maps'=>array('invoice_status_text'=>$status_maps)),
			));
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1364', 'msg'=>'Unable to get the list of registrations', 'err'=>$rc['err']));
		}
		if( !isset($rc['registrations']) ) {
			return array('stat'=>'ok', 'registrations'=>array());
		}
		$registrations = $rc['registrations'];

	} else {
		$strsql = "SELECT ciniki_event_registrations.id, "
			. "ciniki_event_registrations.customer_id, "
			. "IFNULL(ciniki_customers.display_name, '') AS customer_name, "
			. "ciniki_event_registrations.num_tickets, "
			. "ciniki_event_registrations.invoice_id "
			. "FROM ciniki_event_registrations "
			. "LEFT JOIN ciniki_customers ON (ciniki_event_registrations.customer_id = ciniki_customers.id "
				. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. ") "
			. "WHERE ciniki_event_registrations.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_event_registrations.event_id = '" . ciniki_core_dbQuote($ciniki, $args['event_id']) . "' "
			. "ORDER BY ciniki_customers.last, ciniki_customers.first "
			. "";
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.events', array(
			array('container'=>'registrations', 'fname'=>'id', 'name'=>'registration',
				'fields'=>array('id', 'customer_id', 'customer_name', 'num_tickets', 'invoice_id')),
			));
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1521', 'msg'=>'Unable to get the list of registrations', 'err'=>$rc['err']));
		}
		if( !isset($rc['registrations']) ) {
			return array('stat'=>'ok', 'registrations'=>array());
		}
		$registrations = $rc['registrations'];
	}

	//
	// FIXME: check for status of invoices
	//
	// if( isset($modules['ciniki.pos']) ) {
	// }

	return array('stat'=>'ok', 'registrations'=>$registrations);
}
?>
