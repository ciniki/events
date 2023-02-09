<?php
//
// Description
// -----------
// The module flags
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_events_flags($ciniki, $modules) {
    $flags = array(
        // 0x01
        array('flag'=>array('bit'=>'1', 'name'=>'Registrations')),
        array('flag'=>array('bit'=>'2', 'name'=>'Online Registrations')),
        array('flag'=>array('bit'=>'3', 'name'=>'Status')),
        array('flag'=>array('bit'=>'4', 'name'=>'Individual Priced Tickets')),
        // 0x10
        array('flag'=>array('bit'=>'5', 'name'=>'Categories')),
//      array('flag'=>array('bit'=>'6', 'name'=>'')),
//      array('flag'=>array('bit'=>'7', 'name'=>'')),
//      array('flag'=>array('bit'=>'8', 'name'=>'')),
        // 0x0100
        array('flag'=>array('bit'=>'9', 'name'=>'Ticket Maps')),
        array('flag'=>array('bit'=>'10', 'name'=>'Ticket Groups')),
        array('flag'=>array('bit'=>'11', 'name'=>'Expenses')),
//      array('flag'=>array('bit'=>'12', 'name'=>'')),
        // 0x0100
        array('flag'=>array('bit'=>'9', 'name'=>'Ticket Maps')),
//        array('flag'=>array('bit'=>'10', 'name'=>'Ticket Groups')),
//      array('flag'=>array('bit'=>'11', 'name'=>'')),
//      array('flag'=>array('bit'=>'12', 'name'=>'')),
        );

    return array('stat'=>'ok', 'flags'=>$flags);
}
?>
