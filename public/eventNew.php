<?php
//
// Description
// ===========
// This method returns the list of categories and web collections if enabled for a new event.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business the event is attached to.
// event_id:		The ID of the event to get the details for.
// 
// Returns
// -------
// <event id="419" name="Event Name" url="http://myevent.com" 
//		description="Event description" start_date="July 18, 2012" end_date="July 19, 2012"
//		date_added="2012-07-19 03:08:05" last_updated="2012-07-19 03:08:05" />
//
function ciniki_events_eventNew($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'categories'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Categories'),
		'webcollections'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Web Collections'),
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
    $rc = ciniki_events_checkAccess($ciniki, $args['business_id'], 'ciniki.events.eventNew'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
	$modules = $rc['modules'];

	//
	// Check if all tags should be returned
	//
	$categories = array();
	if( ($ciniki['business']['modules']['ciniki.events']['flags']&0x10) > 0
		&& isset($args['categories']) && $args['categories'] == 'yes' 
		) {
		//
		// Get the available tags
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsList');
		$rc = ciniki_core_tagsList($ciniki, 'ciniki.events', $args['business_id'], 
			'ciniki_event_tags', 10);
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2165', 'msg'=>'Unable to get list of categories', 'err'=>$rc['err']));
		}
		if( isset($rc['tags']) ) {
			$categories = $rc['tags'];
		}
	}

	//
	// Get the list of web collections, and which ones this event is attached to
	//
	$webcollections = array();
	if( isset($args['webcollections']) && $args['webcollections'] == 'yes'
		&& isset($ciniki['business']['modules']['ciniki.web']) 
		&& ($ciniki['business']['modules']['ciniki.web']['flags']&0x08) == 0x08
		) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'hooks', 'webCollectionList');
		$rc = ciniki_web_hooks_webCollectionList($ciniki, $args['business_id'],
			array());
		if( $rc['stat'] != 'ok' ) {	
			return $rc;
		}
		if( isset($rc['collections']) ) {
			$webcollections = $rc['collections'];
		}
	}

	return array('stat'=>'ok', 'categories'=>$categories, 'webcollections'=>$webcollections);
}
?>
