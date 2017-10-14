<?php
//
// Description
// -----------
// This function will return the list of options for the module that can be set for the website.
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// business_id:     The ID of the business to get events for.
//
// args:            The possible arguments for posts
//
//
// Returns
// -------
//
function ciniki_events_hooks_webOptions(&$ciniki, $business_id, $args) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['business']['modules']['ciniki.events']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.2', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Get the settings from the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_web_settings', 'business_id', $business_id, 'ciniki.web', 'settings', 'page-events');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['settings']) ) {
        $settings = array();
    } else {
        $settings = $rc['settings'];
    }


    $options = array();
    $options[] = array(
        'label'=>'Display Format',
        'setting'=>'page-events-display-format', 
        'type'=>'toggle',
        'value'=>(isset($settings['page-events-display-format'])?$settings['page-events-display-format']:'cilist'),
        'toggles'=>array(
            array('value'=>'cilist', 'label'=>'Date List'),
            array('value'=>'imagelist', 'label'=>'Image List'),
            ),
        );

    $options[] = array(
        'label'=>'Separate Current Events',
        'setting'=>'page-events-current', 
        'type'=>'toggle',
        'value'=>(isset($settings['page-events-current'])?$settings['page-events-current']:'no'),
        'toggles'=>array(
            array('value'=>'no', 'label'=>'No'),
            array('value'=>'yes', 'label'=>'Yes'),
            ),
        );
    $options[] = array(
        'label'=>'Include Past Events',
        'setting'=>'page-events-past', 
        'type'=>'toggle',
        'value'=>(isset($settings['page-events-past'])?$settings['page-events-past']:'no'),
        'toggles'=>array(
            array('value'=>'no', 'label'=>'No'),
            array('value'=>'yes', 'label'=>'Yes'),
            ),
        );

    $options[] = array(
        'label'=>'Single List',
        'setting'=>'page-events-single-list', 
        'type'=>'toggle',
        'value'=>(isset($settings['page-events-single-list'])?$settings['page-events-single-list']:'no'),
        'toggles'=>array(
            array('value'=>'no', 'label'=>'No'),
            array('value'=>'yes', 'label'=>'Yes'),
            ),
        );

//  $options[] = array('option'=>array(
//      'label'=>'Hide empty upcoming',
//      'setting'=>'page-events-upcoming-empty-hide', 
//      'type'=>'toggle',
//      'value'=>(isset($settings['page-events-upcoming-empty-hide'])?$settings['page-events-upcoming-empty-hide']:'no'),
//      'dependency'=>'page-events-past',
//      'dependency_value'=>'yes',
//      'toggles'=>array(
//          array('toggle'=>array('value'=>'no', 'label'=>'No')),
//          array('toggle'=>array('value'=>'yes', 'label'=>'Yes')),
//          ),
//      ));
    
    //
    // Categories enabled
    //
    if( ($ciniki['business']['modules']['ciniki.events']['flags']&0x10) > 0 ) {
        $options[] = array(
            'label'=>'Display Categories',
            'setting'=>'page-events-categories-display', 
            'type'=>'toggle',
            'value'=>(isset($settings['page-events-categories-display'])?$settings['page-events-categories-display']:'off'),
            'toggles'=>array(
                array('value'=>'off', 'label'=>'Off'),
                array('value'=>'submenu', 'label'=>'Menu'),
                ),
            );
    }

    $pages['ciniki.events'] = array('name'=>'Events', 'options'=>$options);

    //
    // For specific pages, no as many options are required
    //
    $options = array();
    $options[] = array(
        'label'=>'Display Format',
        'setting'=>'page-events-display-format', 
        'type'=>'toggle',
        'value'=>(isset($settings['page-events-display-format'])?$settings['page-events-display-format']:'cilist'),
        'toggles'=>array(
            array('value'=>'cilist', 'label'=>'Date List'),
            array('value'=>'imagelist', 'label'=>'Image List'),
            ),
        );
    $pages['ciniki.events.upcoming'] = array('name'=>'Events - Upcoming', 'options'=>$options);
    $pages['ciniki.events.past'] = array('name'=>'Events - Past', 'options'=>$options);

    return array('stat'=>'ok', 'pages'=>$pages);
}
?>
