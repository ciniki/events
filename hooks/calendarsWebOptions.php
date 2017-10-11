<?php
//
// Description
// -----------
// This function will return the calendar options for the this module.
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// business_id:     The ID of the business to get exhibitions for.
//
// args:            The possible arguments for posts
//
//
// Returns
// -------
//
function ciniki_events_hooks_calendarsWebOptions(&$ciniki, $business_id, $args) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['business']['modules']['ciniki.events']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.67', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    $settings = $args['settings'];

    $options = array();
    $options[] = array(
        'label'=>'Events Display Times',
        'setting'=>'ciniki-events-display-times',
        'type'=>'toggle',
        'value'=>(isset($settings['ciniki-events-display-times'])?$settings['ciniki-events-display-times']:'no'),
        'toggles'=>array(
            array('value'=>'no', 'label'=>'No'),
            array('value'=>'yes', 'label'=>'Yes'),
            ),
    );

    return array('stat'=>'ok', 'options'=>$options);
}
?>
