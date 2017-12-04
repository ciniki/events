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
function ciniki_events_hooks_getObjectName($ciniki, $tnid, $args) {

    // Set the default to not used
    $used = 'no';
    $count = 0;
    $msg = '';

    if( isset($args['object']) && $args['object'] == 'ciniki.events.event' 
        && isset($args['object_id']) && $args['object_id'] != '' ) {
        $strsql = "SELECT name "
            . "FROM ciniki_events "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.events', 'event');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['event']['name']) ) {
            return array('stat'=>'ok', 'name'=>$rc['event']['name']);
        }
    }
    
    return array('stat'=>'noexist', 'err'=>array('code'=>'ciniki.events.1', 'msg'=>'Could not find event'));
}
?>
