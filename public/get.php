<?php
//
// Description
// ===========
// This function will return all the details for a events.
//
// Arguments
// ---------
// user_id: 		The user making the request
// 
// Returns
// -------
//
function ciniki_events_get($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
        'event_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No events specified'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    require_once($ciniki['config']['core']['modules_dir'] . '/events/private/checkAccess.php');
    $rc = ciniki_events_checkAccess($ciniki, $args['business_id'], 'ciniki.events.get'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/users/private/dateFormat.php');
	$date_format = ciniki_users_dateFormat($ciniki);

	$strsql = "SELECT ciniki_events.id, name, url, description, "
		. "DATE_FORMAT(start_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS start_date, "
		. "DATE_FORMAT(end_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS end_date, "
		. "date_added, last_updated "
		. "FROM ciniki_events "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_events.id = '" . ciniki_core_dbQuote($ciniki, $args['event_id']) . "' "
		. "";
	
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'events', 'event');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['event']) ) {
		return array('stat'=>'ok', 'err'=>array('pkg'=>'ciniki', 'code'=>'617', 'msg'=>'Unable to find event'));
	}

	return array('stat'=>'ok', 'event'=>$rc['event']);
}
?>
