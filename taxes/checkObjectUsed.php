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
function ciniki_events_taxes_checkObjectUsed($ciniki, $modules, $business_id, $object, $object_id) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');

    // Set the default to not used
    $used = 'no';
    $count = 0;
    $msg = '';

    //
    // There are only tax types in this module
    //
    if( $object == 'ciniki.taxes.type' ) {
        //
        // Check the event prices
        //
        $strsql = "SELECT 'items', COUNT(*) "
            . "FROM ciniki_event_prices "
            . "WHERE taxtype_id = '" . ciniki_core_dbQuote($ciniki, $object_id) . "' "
            . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "";
        $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.events', 'num');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['num']['items']) && $rc['num']['items'] > 0 ) {
            $used = 'yes';
            $count = $rc['num']['items'];
            $msg = "There " . ($count==1?'is':'are') . " $count event price" . ($count==1?'':'s') . " still using this tax type.";
        }
    }

    return array('stat'=>'ok', 'used'=>$used, 'count'=>$count, 'msg'=>$msg);
}
?>
