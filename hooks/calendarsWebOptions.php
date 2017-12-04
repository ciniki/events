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
// tnid:     The ID of the tenant to get exhibitions for.
//
// args:            The possible arguments for posts
//
//
// Returns
// -------
//
function ciniki_events_hooks_calendarsWebOptions(&$ciniki, $tnid, $args) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.events']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.67', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    $settings = $args['settings'];

    $options = array();
    $options[] = array(
        'label'=>'Event Title Prefix',
        'setting'=>'ciniki-events-prefix',
        'type'=>'text',
        'value'=>(isset($settings['ciniki-events-prefix'])?$settings['ciniki-events-prefix']:''),
        );
    $options[] = array(
        'label'=>'Event Legend Name',
        'setting'=>'ciniki-events-legend-title',
        'type'=>'text',
        'value'=>(isset($settings['ciniki-events-legend-title'])?$settings['ciniki-events-legend-title']:''),
        );
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
    $options[] = array(
        'label'=>'Events Background Colour',
        'setting'=>'ciniki-events-colour-background', 
        'type'=>'colour',
        'value'=>(isset($settings['ciniki-events-colour-background'])?$settings['ciniki-events-colour-background']:'no'),
        );
    $options[] = array(
        'label'=>'Events Border Colour',
        'setting'=>'ciniki-events-colour-border', 
        'type'=>'colour',
        'value'=>(isset($settings['ciniki-events-colour-border'])?$settings['ciniki-events-colour-border']:'no'),
        );
    $options[] = array(
        'label'=>'Events Font Colour',
        'setting'=>'ciniki-events-colour-font', 
        'type'=>'colour',
        'value'=>(isset($settings['ciniki-events-colour-font'])?$settings['ciniki-events-colour-font']:'no'),
        );

    return array('stat'=>'ok', 'options'=>$options);
}
?>
