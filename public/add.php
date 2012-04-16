<?php
//
// Description
// -----------
// This method will add a new website to a business.  
//
// Info
// ----
// Status: beta
//
// Arguments
// ---------
// api_key:
// auth_token:
// name:			(optional) The name of the website, typically the domain name.
// hostname:		The hostname for the website, if different from the domain name.
//
// Returns
// -------
// <rsp stat="ok">
//
function ciniki_events_add($ciniki) {
	//
	// Find all the required and optional arguments
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		'name'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No name specified'), 
		'url'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No url specified'), 
		'description'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No description specified'), 
		'start_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'errmsg'=>'No start date specified'), 
		'end_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'errmsg'=>'No end date specified'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access to business_id as owner
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/events/private/checkAccess.php');
	$ac = ciniki_events_checkAccess($ciniki, $args['business_id'], 'ciniki.events.add');
	if( $ac['stat'] != 'ok' ) {
		return $ac;
	}

	//
	// Start transaction
	//
	require($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionStart.php');
	require($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionRollback.php');
	require($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionCommit.php');
	require($ciniki['config']['core']['modules_dir'] . '/core/private/dbInsert.php');
	require($ciniki['config']['core']['modules_dir'] . '/core/private/dbAddChangeLog.php');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'events');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Add site to web_sites table
	// FIXME: Add ability to set modules when site is added, right now default to most apps on
	//
	$strsql = "INSERT INTO ciniki_events (uuid, business_id, "
		. "name, url, description, start_date, end_date, "
		. "date_added, last_updated ) VALUES ( "
		. "UUID(), "
		. "'" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['name']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['url']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['description']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['start_date']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['end_date']) . "', "
		. "UTC_TIMESTAMP(), UTC_TIMESTAMP())";
	$rc = ciniki_core_dbInsert($ciniki, $strsql, 'events');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'events');
		return $rc;
	}
	if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'events');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'615', 'msg'=>'Unable to add event'));
	}
	$event_id = $rc['insert_id'];

	//
	// Add all the fields to the change log
	//
	$changelog_fields = array(
		'name',
		'url',
		'description',
		'start_date',
		'end_date',
		);
	foreach($changelog_fields as $field) {
		if( isset($args[$field]) && $args[$field] != '' ) {
			$rc = ciniki_core_dbAddChangeLog($ciniki, 'events', $args['business_id'], 
				'ciniki_events', $event_id, $field, $args[$field]);
		}
	}

	//
	// Commit the transaction
	//
	$rc = ciniki_core_dbTransactionCommit($ciniki, 'events');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok', 'id'=>$event_id);
}
?>
