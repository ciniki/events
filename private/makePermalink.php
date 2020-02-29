<?php
//
// Description
// -----------
// This function creates the permalink for an event, given the name and start_date
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_events_makePermalink(&$ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
    if( !preg_match("/" . $args['start_date']->format('Y') . "/", $args['name']) ) {
        $permalink = ciniki_core_makePermalink($ciniki, $args['name'] . '-' . $args['start_date']->format('M-j-Y'));
    } else {
        $permalink = ciniki_core_makePermalink($ciniki, $args['name']);
    }

    return $permalink;
}
?>
