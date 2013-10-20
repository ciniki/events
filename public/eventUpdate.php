<?php
//
// Description
// ===========
// This method will update an event in the database.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business the event is attached to.
// name:			(optional) The new name of the event.
// url:				(optional) The new URL for the event website.
// description:		(optional) The new description for the event.
// start_date:		(optional) The new date the event starts.  
// end_date:		(optional) The new date the event ends, if it's longer than one day.
// 
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_events_eventUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'event_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Event'), 
		'name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Name'), 
		'permalink'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Permalink'), 
		'url'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'URL'), 
		'description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Description'), 
		'num_tickets'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Number of Tickets'),
		'reg_flags'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Registration Flags'),
		'start_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'name'=>'Start Date'), 
		'end_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'name'=>'End Date'), 
		'primary_image_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Image'), 
		'long_description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Long Description'), 
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
    $rc = ciniki_events_checkAccess($ciniki, $args['business_id'], 'ciniki.events.eventUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// Get the existing event details
	//
	$strsql = "SELECT uuid FROM ciniki_events "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['event_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.events', 'event');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['event']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1298', 'msg'=>'Event not found'));
	}
	$event = $rc['event'];

	if( isset($args['name']) ) {
		$args['permalink'] = preg_replace('/ /', '-', preg_replace('/[^a-z0-9 \-]/', '', strtolower($args['name'])));
		//
		// Make sure the permalink is unique
		//
		$strsql = "SELECT id, name, permalink FROM ciniki_events "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
			. "AND id <> '" . ciniki_core_dbQuote($ciniki, $args['event_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.events', 'event');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( $rc['num_rows'] > 0 ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1299', 'msg'=>'You already have an event with this name, please choose another name'));
		}
	}

	//
	// Update the event in the database
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
	return ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.events.event', $args['event_id'], $args);
}
?>
