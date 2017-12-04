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
function ciniki_events_hooks_checkObjectUsed($ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');

    // Set the default to not used
    $used = 'no';
    $count = 0;
    $msg = '';

    //
    // Check if customer is used anywhere
    //
    if( $args['object'] == 'ciniki.customers.customer' ) {
        //
        // Check the invoice customers
        //
        $strsql = "SELECT 'items', COUNT(*) "
            . "FROM ciniki_event_registrations "
            . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.events', 'num');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['num']['items']) && $rc['num']['items'] > 0 ) {
            $used = 'yes';
            $count = $rc['num']['items'];
            $msg .= ($msg!=''?' ':'') . "There " . ($count==1?'is':'are') . " $count event registration" . ($count==1?'':'s') . " for this customer.";
        }
    }

    //
    // Check if image is used anywhere
    //
    if( $args['object'] == 'ciniki.images.image' ) {
        //
        // Check the events
        //
        $count = 0;
        $strsql = "SELECT COUNT(*) AS items "
            . "FROM ciniki_events "
            . "WHERE primary_image_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.events', 'num');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( $rc['num'] > 0 ) {
            $count += $rc['num'];
        }

        //
        // Check the event images
        //
        $strsql = "SELECT COUNT(*) AS items "
            . "FROM ciniki_event_images "
            . "WHERE image_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.events', 'num');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( $rc['num'] > 0 ) {
            $count += $rc['num'];
        }

        //
        // Check if used
        //
        if( $count > 0 ) {
            $used = 'yes';
            $msg .= ($msg!=''?' ':'') . "There " . ($rc['num']==1?'is':'are') . " {$rc['num']} event" . ($rc['num']==1?'':'s') . " using this image.";
        }
    }

    return array('stat'=>'ok', 'used'=>$used, 'count'=>$count, 'msg'=>$msg);
}
?>
