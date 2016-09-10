<?php
//
// Description
// -----------
// This function will return a list of user interface settings for the module.
//
// Arguments
// ---------
// ciniki:
// business_id:     The ID of the business to get events for.
//
// Returns
// -------
//
function ciniki_events_hooks_uiSettings($ciniki, $business_id, $args) {

    //
    // Any settings for the module
    //
    $settings = array();

    //
    // Setup the menu items
    //
    $menu = array();

    //
    // Check permissions for what menu items should be available
    //
    if( isset($ciniki['business']['modules']['ciniki.events'])
        && (isset($args['permissions']['owners'])
            || isset($args['permissions']['employees'])
            || isset($args['permissions']['resellers'])
            || ($ciniki['session']['user']['perms']&0x01) == 0x01
            )
        ) {
        $menu_item = array(
            'priority'=>3000,
            'label'=>'Events', 
            'edit'=>array('app'=>'ciniki.events.main'),
            );
        $menu[] = $menu_item;
    } 

    return array('stat'=>'ok', 'settings'=>$settings, 'menu_items'=>$menu);  
}
?>
