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
// tnid:     The ID of the tenant to get events for.
//
// args:            The possible arguments for posts
//
//
// Returns
// -------
//
function ciniki_events_hooks_webOptions(&$ciniki, $tnid, $args) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.events']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.2', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Get the settings from the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_web_settings', 'tnid', $tnid, 'ciniki.web', 'settings', '');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['settings']) ) {
        $settings = array();
    } else {
        $settings = $rc['settings'];
    }


    $options = array();
    $option = array(
        'label'=>'Display Format',
        'setting'=>'page-events-display-format', 
        'type'=>'toggle',
        'value'=>(isset($settings['page-events-display-format'])?$settings['page-events-display-format']:'cilist'),
        'toggles'=>array(
            array('value'=>'cilist', 'label'=>'Date List'),
            array('value'=>'imagelist', 'label'=>'Image List'),
            ),
        );
    if( isset($settings['site-theme']) && $settings['site-theme'] == 'twentyone' ) {
        $option['toggles'] = array(
            array('value'=>'imagelist', 'label'=>'Image List'),
            array('value'=>'tradingcards', 'label'=>'Trading Cards'),
            );
    }
    $options[] = $option;

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
    if( ($ciniki['tenant']['modules']['ciniki.events']['flags']&0x10) > 0 ) {
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

    $options[] = array(
        'label'=>'Introduction',
        'setting'=>'page-events-content', 
        'type'=>'textarea',
        'value'=>(isset($settings['page-events-content'])?$settings['page-events-content']:''),
        );

    $pages['ciniki.events'] = array('name'=>'Events', 'options'=>$options);

    //
    // For specific pages, no as many options are required
    //
    $options = array();
    $option = array(
        'label'=>'Display Format',
        'setting'=>'page-events-display-format', 
        'type'=>'toggle',
        'value'=>(isset($settings['page-events-display-format'])?$settings['page-events-display-format']:'cilist'),
        'toggles'=>array(
            array('value'=>'cilist', 'label'=>'Date List'),
            array('value'=>'imagelist', 'label'=>'Image List'),
            ),
        );
    if( isset($settings['site-theme']) && $settings['site-theme'] == 'twentyone' ) {
        $option['toggles'] = array(
            array('value'=>'imagelist', 'label'=>'Image List'),
            array('value'=>'tradingcards', 'label'=>'Trading Cards'),
            );
    }
    $options[] = $option;
    $pages['ciniki.events.upcoming'] = array('name'=>'Events - Upcoming', 'options'=>$options);
    $pages['ciniki.events.past'] = array('name'=>'Events - Past', 'options'=>$options);

    return array('stat'=>'ok', 'pages'=>$pages);
}
?>
