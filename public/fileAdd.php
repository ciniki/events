<?php
//
// Description
// ===========
// This method will add a new file to the files table.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to add the file to.
// event_id:			The ID of the event the file is attached to.
// name:				The name of the file.
// description:			(optional) The extended description of the file, can be much longer than the name.
// webflags:			(optional) How the file is shared with the public and customers.  
//						The default is the file is public.
//
//						0x01 - Hidden, unavailable on the website
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_events_fileAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'event_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Event'),
        'name'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Name'), 
        'description'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Description'), 
        'webflags'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Web Flags'), 
		'publish_date'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'type'=>'date', 'name'=>'Publish Date'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

	$name = $args['name'];
	$args['permalink'] = preg_replace('/ /', '-', preg_replace('/[^a-z0-9 ]/', '', strtolower($name)));

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'events', 'private', 'checkAccess');
    $rc = ciniki_events_checkAccess($ciniki, $args['business_id'], 'ciniki.events.fileAdd'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//  
	// Turn off autocommit
	//  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.events');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Check the permalink doesn't already exist
	//
	$strsql = "SELECT id, name, permalink FROM ciniki_event_files "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND event_id = '" . ciniki_core_dbQuote($ciniki, $args['event_id']) . "' "
		. "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.events', 'file');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( $rc['num_rows'] > 0 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1317', 'msg'=>'You already have a file with this name, please choose another name'));
	}

    //
    // Check to see if an image was uploaded
    //
    if( isset($_FILES['uploadfile']['error']) && $_FILES['uploadfile']['error'] == UPLOAD_ERR_INI_SIZE ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1327', 'msg'=>'Upload failed, file too large.'));
    }
    // FIXME: Add other checkes for $_FILES['uploadfile']['error']

	//
	// Make sure a file was submitted
	//
	if( !isset($_FILES) || !isset($_FILES['uploadfile']) || $_FILES['uploadfile']['tmp_name'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1319', 'msg'=>'No file specified.'));
	}

	$args['org_filename'] = $_FILES['uploadfile']['name'];
	$args['extension'] = preg_replace('/^.*\.([a-zA-Z]+)$/', '$1', $args['org_filename']);

	//
	// Check the extension is a PDF, currently only accept PDF files
	//
	if( $args['extension'] != 'pdf' ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1320', 'msg'=>'The file must be a PDF file.'));
	}

	//
	// Get a new UUID
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
	$rc = ciniki_core_dbUUID($ciniki, 'ciniki.events');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.events');
		return $rc;
	}
	$args['uuid'] = $rc['uuid'];

	$file_contents = file_get_contents($_FILES['uploadfile']['tmp_name']);

	//
	// Add the file to the database
	//
	$strsql = "INSERT INTO ciniki_event_files (uuid, business_id, "
		. "event_id, extension, name, permalink, webflags, "
		. "description, org_filename, publish_date, binary_content, "
		. "date_added, last_updated) VALUES ("
		. "'" . ciniki_core_dbQuote($ciniki, $args['uuid']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['event_id']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['extension']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['name']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['webflags']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['description']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['org_filename']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['publish_date']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $file_contents) . "', "
		. "UTC_TIMESTAMP(), UTC_TIMESTAMP())"
		. "";
	$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.events');
	if( $rc['stat'] != 'ok' ) { 
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.events');
		return $rc;
	}
	if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.events');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1326', 'msg'=>'Unable to add file'));
	}
	$file_id = $rc['insert_id'];

	//
	// Add all the fields to the change log
	//
	$changelog_fields = array(
		'uuid',
		'event_id',
		'extension',
		'name',
		'permalink',
		'webflags',
		'description',
		'org_filename',
		'publish_date',
		);
	foreach($changelog_fields as $field) {
		if( isset($args[$field]) ) {
			$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.events', 
				'ciniki_event_history', $args['business_id'], 
				1, 'ciniki_event_files', $file_id, $field, $args[$field]);
		}
	}

	//
	// Commit the database changes
	//
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.events');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Update the last_change date in the business modules
	// Ignore the result, as we don't want to stop user updates if this fails.
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
	ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'events');

	$ciniki['syncqueue'][] = array('push'=>'ciniki.events.file', 
		'args'=>array('id'=>$file_id));

	return array('stat'=>'ok', 'id'=>$file_id);
}
?>
