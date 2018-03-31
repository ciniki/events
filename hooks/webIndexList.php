<?php
//
// Description
// -----------
// This function returns the list of objects and object_ids that should be indexed on the website.
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant to get events for.
//
// Returns
// -------
//
function ciniki_events_hooks_webIndexList($ciniki, $tnid, $args) {

    $objects = array();

    //
    // Get the list of items that should be in the index
    //
    $strsql = "SELECT CONCAT('ciniki.events.event.', id) AS oid, 'ciniki.events.event' AS object, id AS object_id "
        . "FROM ciniki_events "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND (flags&0x01) = 0x01 "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.events', array(
        array('container'=>'objects', 'fname'=>'oid', 'fields'=>array('object', 'object_id')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['objects']) ) {
        $objects = $rc['objects'];
    }

    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
