<?php
//
// Description
// -----------
// This method will update an existing event link to an event.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_events_linkUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'link_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Link'),
        'name'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Name'), 
        'url'=>array('required'=>'no', 'blank'=>'no', 'name'=>'URL'),
        'description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Description'),
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
    $rc = ciniki_events_checkAccess($ciniki, $args['business_id'], 'ciniki.events.linkUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

	//
	// If the url is being updated, check the new one does not exist
	//
	if( isset($args['url']) && $args['url'] != '' ) {
		//
		// Get the existing link
		//
		$strsql = "SELECT id, event_id, name, url, description "
			. "FROM ciniki_event_links "
			. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['link_id']) . "' "
			. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.events', 'link');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['link']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2164', 'msg'=>'The event link does not exist'));
		}
		$link = $rc['link'];

		//
		// Check the url
		//
		$strsql = "SELECT id "
			. "FROM ciniki_event_links "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND url = '" . ciniki_core_dbQuote($ciniki, $args['url']) . "' "
			. "AND event_id = '" . ciniki_core_dbQuote($ciniki, $link['event_id']) . "' "
			. "AND id <> '" . ciniki_core_dbQuote($ciniki, $link['id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.events', 'link');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['link']) || (isset($rc['rows']) && count($rc['rows']) > 0) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1927', 'msg'=>'You already have a event link with that url, please choose another'));
		}
	}

	//
	// Upate the event link
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
	$rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.events.link', 
		$args['link_id'], $args, 0x07);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok');
}
?>
