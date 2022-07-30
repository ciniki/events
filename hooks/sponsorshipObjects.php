<?php
//
// Description
// -----------
// Return the list of objects and ids available for sponsorship.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_events_hooks_sponsorshipObjects(&$ciniki, $tnid, $args) {

    $objects = array();
    
    //
    // Get the list of events that are upcoming for adding a sponsorship package to
    //
    $strsql = "SELECT events.id, "
        . "events.name, "
        . "events.start_date, "
        . "DATE_FORMAT(events.start_date, '%b %e, %Y') AS event_date "
        . "FROM ciniki_events AS events "
        . "WHERE events.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND (events.start_date > NOW() "
        . "";
    if( isset($args['object']) && $args['object'] == 'ciniki.events.event' && isset($args['object_id']) ) {
        $strsql .= "OR events.id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' ";
    }
    $strsql .= ") ";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.events', array(
        array('container'=>'events', 'fname'=>'name', 'fields'=>array('id', 'name', 'start_date', 'event_date')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.92', 'msg'=>'Unable to load events', 'err'=>$rc['err']));
    }
    $events = isset($rc['events']) ? $rc['events'] : array();

    //
    // Create the object array
    //
    foreach($events as $eid => $event) {
        if( $event['event_date'] != '' && $event['start_date'] != '0000-00-00' ) {
            $event['name'] = $event['name'] . ' - ' . $event['event_date'];
        }
        $objects["ciniki.events.event.{$event['id']}"] = array(
            'id' => 'ciniki.events.event.' . $event['id'],
            'object' => 'ciniki.events.event',
            'object_id' => $event['id'],
            'full_name' => 'Event - ' . $event['name'],
            'name' => $event['name'],
            );
    }

    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
