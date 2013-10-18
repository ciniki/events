<?php
//
// Description
// ===========
// This method will add a new registration for an event.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to add the file to.
// event_id:			The ID of the event the file is attached to.
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_events_registrationAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'event_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Event'),
		'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'),
		'num_tickets'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Number of Tickets'),
		'invoice_id'=>array('required'=>'no', 'blank'=>'no', 'default'=>'0', 'name'=>'Invoice'),
        'customer_notes'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Customer Notes'), 
        'notes'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Notes'), 
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
    $rc = ciniki_events_checkAccess($ciniki, $args['business_id'], 'ciniki.events.registrationAdd'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// Add the registration to the database
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	return ciniki_core_objectAdd($ciniki, 'ciniki', 'events', 'registration', $args);
}
?>
