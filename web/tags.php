<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_events_web_tags($ciniki, $settings, $tnid, $tag_type) {

    $strsql = "SELECT DISTINCT ciniki_event_tags.permalink, "
        . "ciniki_event_tags.tag_name "
        . "FROM ciniki_events, ciniki_event_tags "
        . "WHERE ciniki_events.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_events.id = ciniki_event_tags.event_id "
        . "AND ciniki_event_tags.tag_type = '" . ciniki_core_dbQuote($ciniki, $tag_type) . "' "
        . "AND ciniki_event_tags.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY ciniki_event_tags.tag_name ASC "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.events', array(
        array('container'=>'tags', 'fname'=>'permalink', 
            'fields'=>array('permalink', 'tag_name')),
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
