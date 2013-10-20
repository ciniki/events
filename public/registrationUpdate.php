<?php
//
// Description
// ===========
// This method will update an event registration in the database.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business the event is attached to.
// 
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_events_registrationUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'registration_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Registration'), 
		'customer_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Customer'),
		'num_tickets'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Number of Tickets'),
        'customer_notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customer Notes'), 
        'notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Notes'), 
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
    $rc = ciniki_events_checkAccess($ciniki, $args['business_id'], 'ciniki.events.update'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// Update the registration in the database
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
	return ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.events.registration', $args['registration_id'], $args);
}
?>
