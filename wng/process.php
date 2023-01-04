<?php
//
// Description
// -----------
// This function will return the blocks for the website.
//
// Arguments
// ---------
// ciniki:
// tnid:            The ID of the tenant.
// args:            The possible arguments for.
//
//
// Returns
// -------
//
function ciniki_events_wng_process(&$ciniki, $tnid, &$request, $section) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.events']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.100', 'msg'=>"I'm sorry, the section you requested does not exist."));
    }

    //
    // Check to make sure the report is specified
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.101', 'msg'=>"No section specified."));
    }

    if( $section['ref'] == 'ciniki.events.upcoming' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'events', 'wng', 'upcomingProcess');
        return ciniki_events_wng_upcomingProcess($ciniki, $tnid, $request, $section);
    } elseif( $section['ref'] == 'ciniki.events.past' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'events', 'wng', 'pastProcess');
        return ciniki_events_wng_pastProcess($ciniki, $tnid, $request, $section);
    }

    return array('stat'=>'ok');
}
?>
