<?php
//
// Description
// ===========
// This method will return the list of categories used in the events.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant to get the list from.
// 
// Returns
// -------
//
function ciniki_events_tagList($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'tag_type'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Type'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'events', 'private', 'checkAccess');
    $rc = ciniki_events_checkAccess($ciniki, $args['tnid'], 'ciniki.events.tagList'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Load the status maps for the text description of each status
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'events', 'private', 'maps');
    $rc = ciniki_events_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Get the distinct list of tags
    //
    $strsql = "SELECT DISTINCT tag_name, permalink "
        . "FROM ciniki_event_tags "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND tag_type = '" . ciniki_core_dbQuote($ciniki, $args['tag_type']) . "' "
        . "ORDER BY tag_name "
        . "";
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.events', array(
        array('container'=>'tags', 'fname'=>'tag_name', 'name'=>'tag',
            'fields'=>array('tag_type', 'tag_name', 'permalink')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['tags']) ) {
        return array('stat'=>'ok', 'tags'=>array());
    }

    return array('stat'=>'ok', 'tags'=>$rc['tags']);
}
?>
