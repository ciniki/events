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
function ciniki_events_tagGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'tag_type'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Type'),
        'tag_permalink'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Permalink'),
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
    $rc = ciniki_events_checkAccess($ciniki, $args['tnid'], 'ciniki.events.tagGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Get the settings for the events
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash'); 
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_event_settings', 
        'tnid', $args['tnid'], 'ciniki.events', 'settings', 
        "tag-" . $args['tag_type'] . '-' . $args['tag_permalink']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['settings']) ) {
        $settings = $rc['settings'];
    } else {
        $settings = array();
    }

    $details = array();
    foreach($settings as $setting => $value) {
        $setting = str_replace('tag-' . $args['tag_type'] . '-' . $args['tag_permalink'] . '-', '', $setting);
        $details[$setting] = $value;
    }
    
    return array('stat'=>'ok', 'details'=>$details);
}
?>
