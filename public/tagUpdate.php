<?php
//
// Description
// ===========
// This method will update a tag names in the events.  This can be used to
// merge categories.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant to the item is a part of.
// old_tag: The name of the old tag.
// new_tag: The new name for the tag.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_events_tagUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'tag_type'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Type'),
        'tag_permalink'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Permalink'),
        'image-id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Image'), 
        'synopsis'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Synopsis'), 
        'content'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Content'), 
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
    $rc = ciniki_events_checkAccess($ciniki, $args['tnid'], 'ciniki.events.tagUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $tag_type = $args['tag_type'];
    $tag_permalink = $args['tag_permalink'];
    
    //
    // Get the existing settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash'); 
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_event_settings', 
        'tnid', $args['tnid'], 'ciniki.events', 'settings', "tag-$tag_type-$tag_permalink");
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $settings = array();
    if( isset($rc['settings']) ) {
        $settings = $rc['settings'];
    }

    //  
    // Turn off autocommit
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.events');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    $updated = 0;
    $fields = array('image-id', 'synopsis', 'content');
    foreach($fields as $f) {
        $detail_key = "tag-$tag_type-$tag_permalink-$f";
        if( isset($args[$f]) ) {
            //
            // If existing setting doesn't exist, then update
            //
            if( !isset($settings[$detail_key]) ) {
                $strsql = "INSERT INTO ciniki_event_settings (tnid, detail_key, detail_value, "
                    . "date_added, last_updated) VALUES ("
                    . "' " . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ", '" . ciniki_core_dbQuote($ciniki, $detail_key) . "' "
                    . ", '" . ciniki_core_dbQuote($ciniki, $args[$f]) . "' "
                    . ", UTC_TIMESTAMP(), UTC_TIMESTAMP()) "
                    . "";
                $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.events');
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.events', 
                    'ciniki_event_history', $args['tnid'], 
                    1, 'ciniki_event_settings', $detail_key, 'detail_value', $args[$f]);
                $ciniki['syncqueue'][] = array('push'=>'ciniki.events.setting',
                    'args'=>array('id'=>$detail_key));
                $updated = 1;
            } 

            //
            // Update the existing setting
            //
            elseif( $args[$f] != $settings[$detail_key] ) {
                $strsql = "UPDATE ciniki_event_settings "
                    . "SET detail_value = '" . ciniki_core_dbQuote($ciniki, $args[$f]) . "', "
                    . "last_updated = UTC_TIMESTAMP() "
                    . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . "AND detail_key = '" . ciniki_core_dbQuote($ciniki, $detail_key) . "' "
                    . "";
                $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.events');
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.events', 
                    'ciniki_event_history', $args['tnid'], 
                    2, 'ciniki_event_settings', $detail_key, 'detail_value', $args[$f]);
                $ciniki['syncqueue'][] = array('push'=>'ciniki.events.setting',
                    'args'=>array('id'=>$detail_key));
                $updated = 1;
            }
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
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    if( $updated > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
        ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'events');
    }

    return array('stat'=>'ok');
}
?>
