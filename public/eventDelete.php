<?php
//
// Description
// -----------
// This method will delete a event from the tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the event is attached to.
// event_id:            The ID of the event to be removed.
//
// Returns
// -------
// <rsp stat="ok">
//
function ciniki_events_eventDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'event_id'=>array('required'=>'yes', 'default'=>'', 'blank'=>'yes', 'name'=>'Event'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'events', 'private', 'checkAccess');
    $rc = ciniki_events_checkAccess($ciniki, $args['tnid'], 'ciniki.events.eventDelete');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the tenant storage directory
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'hooks', 'storageDir');
    $rc = ciniki_tenants_hooks_storageDir($ciniki, $args['tnid'], array());
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $tenant_storage_dir = $rc['storage_dir'];

    //
    // Get the uuid of the event to be deleted
    //
    $strsql = "SELECT uuid "
        . "FROM ciniki_events "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['event_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.events', 'event');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['event']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.7', 'msg'=>'The event does not exist'));
    }
    $event_uuid = $rc['event']['uuid'];

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.events');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Remove the images
    //
    $strsql = "SELECT id, uuid, image_id "
        . "FROM ciniki_event_images "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND event_id = '" . ciniki_core_dbQuote($ciniki, $args['event_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.events', 'image');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.events');
        return $rc;
    }
    if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
        $images = $rc['rows'];
        
        foreach($images as $iid => $image) {
            $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.events.image', 
                $image['id'], $image['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.events');
                return $rc; 
            }
        }
    }

    //
    // Remove the files for the event
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_event_files "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND event_id = '" . ciniki_core_dbQuote($ciniki, $args['event_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.events', 'file');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.events');
        return $rc;
    }
    if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
        $files = $rc['rows'];
        foreach($files as $fid => $file) {
            //
            // Remove file from disk
            //
            $storage_filename = $tenant_storage_dir . '/ciniki.events/files/' . $file['uuid'][0] . '/' . $file['uuid'];
            if( file_exists($storage_filename) ) {
                unlink($storage_filename);
            }

            //
            // Remove the object 
            //
            $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.events.file', 
                $file['id'], $file['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.events');
                return $rc; 
            }
        }
    }

    //
    // Remove the prices
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_event_prices "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND event_id = '" . ciniki_core_dbQuote($ciniki, $args['event_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.events', 'price');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.events');
        return $rc;
    }
    if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
        $prices = $rc['rows'];
        foreach($prices as $rid => $price) {
            $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.events.price', $price['id'], $price['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.events');
                return $rc;
            }
        }
    }

    //
    // Remove the registrations
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_event_registrations "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND event_id = '" . ciniki_core_dbQuote($ciniki, $args['event_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.events', 'registration');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.events');
        return $rc;
    }
    if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
        $registrations = $rc['rows'];
        foreach($registrations as $rid => $registration) {
            $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.events.registration',
                $registration['id'],$registration['uuid']);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.events');
                return $rc;
            }
        }
    }

    //
    // Remove any registration questions for this event
    //
/*  $strsql = "SELECT id, uuid "
        . "FROM ciniki_event_registration_questions "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND event_id = '" . ciniki_core_dbQuote($ciniki, $args['event_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.events', 'question');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.events');
        return $rc;
    }
    if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
        $questions = $rc['rows'];
        foreach($questions as $qid => $question) {
            $rc = ciniki_core_objectDelete($ciniki, 'ciniki.events.question', $question['id'], $question['uuid'],
                array('tnid'=>$args['tnid']), 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.events');
                return $rc; 
            }
        }
    }
*/

    //
    // Remove any tags
    //
    if( ($ciniki['tenant']['modules']['ciniki.events']['flags']&0x10) > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsDelete');
        $rc = ciniki_core_tagsDelete($ciniki, 'ciniki.events', 'tag', $args['tnid'],
            'ciniki_event_tags', 'ciniki_event_history', 'event_id', $args['event_id']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.events');
            return $rc;
        }
    }

    //
    // Remove the event from any web collections
    //
    if( isset($ciniki['tenant']['modules']['ciniki.web']) 
        && ($ciniki['tenant']['modules']['ciniki.web']['flags']&0x08) == 0x08
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'hooks', 'webCollectionDeleteObjRef');
        $rc = ciniki_web_hooks_webCollectionDeleteObjRef($ciniki, $args['tnid'],
            array('object'=>'ciniki.events.event', 'object_id'=>$args['event_id']));
        if( $rc['stat'] != 'ok' ) { 
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.events');
            return $rc;
        }
    }

    //
    // Remove the event
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.events.event', 
        $args['event_id'], $event_uuid, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.events');
        return $rc;
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.events');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'events');

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.events.event', 'object_id'=>$args['event_id']));
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'wng', 'indexObject', array('object'=>'ciniki.events.event', 'object_id'=>$args['event_id']));

    return array('stat'=>'ok');
}
?>
