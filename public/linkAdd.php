<?php
//
// Description
// -----------
// This method will add a new event link to an event.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_events_linkAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'event_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Post'),
        'name'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Name'), 
        'url'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'URL'),
        'description'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Description'),
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
    $rc = ciniki_events_checkAccess($ciniki, $args['business_id'], 'ciniki.events.linkAdd'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

	//
	// Check the url does not already exist for this events 
	//
	$strsql = "SELECT id "
		. "FROM ciniki_event_links "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND url = '" . ciniki_core_dbQuote($ciniki, $args['url']) . "' "
		. "AND event_id = '" . ciniki_core_dbQuote($ciniki, $args['event_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.events', 'link');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['link']) || (isset($rc['rows']) && count($rc['rows']) > 0) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2157', 'msg'=>'You already have a event link with that url, please choose another'));
	}

	//
	// Add the event link
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	$rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.events.link', $args, 0x07);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$link_id = $rc['id'];

	return array('stat'=>'ok', 'id'=>$link_id);
}
?>
