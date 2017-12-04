<?php
//
// Description
// ===========
// This method returns the list of objects that can be returned
// as invoice items.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_events_sapos_objectList($ciniki, $tnid) {

    $objects = array(
        //
        // this object should only be added to carts
        //
        'ciniki.events.event' => array(
            'name' => 'Event',
            ),
        'ciniki.events.registration' => array(
            'name' => 'Event Registration',
            ),
        );

    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
