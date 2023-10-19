<?php
//
// Description
// -----------
// Return the list of sections available from the events module
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_events_wng_sections(&$ciniki, $tnid, $args) {

    //
    // Check to make sure blog module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.events']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.99', 'msg'=>'Module not enabled'));
    }

    //
    // The latest blog section
    //
    $sections['ciniki.events.upcoming'] = array(
        'name' => 'Upcoming',
        'module' => 'Events',
        'settings' => array(
            'title' => array('label'=>'Title', 'type'=>'text'),
            'include-current' => array('label'=>'Include Current Events', 'type'=>'toggle', 'default'=>'yes', 'toggles'=>array(
                'no' => 'No',
                'yes' => 'Yes',
                )),
//            'thumbnail-format' => array('label'=>'Thumbnail Format', 'type'=>'toggle', 'default'=>'square-cropped', 
//                'toggles'=>array(
//                    'square-cropped' => 'Cropped',
//                    'square-padded' => 'Padded',
//                )),
//            'thumbnail-padding-color' => array('label'=>'Padding Color', 'type'=>'colour'),
//            'show-date' => array('label'=>'Show Date', 'type'=>'toggle', 'default'=>'yes', 'toggles'=>array(
//                'no' => 'No',
//                'yes' => 'Yes',
//                )),
            'image-position'=>array('label'=>'Image Position', 'type'=>'select', 'default'=>'top-right', 'options'=>array(
                'top-left' => 'Top Left',
                'top-left-inline' => 'Top Left Inline',
                'bottom-left' => 'Bottom Left',
                'top-right' => 'Top Right',
                'top-right-inline' => 'Top Right Inline',
                'bottom-right' => 'Bottom Right',
                )),
            'image-size'=>array('label'=>'Image Size', 'type'=>'toggle', 'default'=>'half', 'toggles'=>array(
                'half' => 'Full',
                'large' => 'Large',
                'medium' => 'Medium',
                'small' => 'Small',
                'tiny' => 'Tiny',
                )),
            'button-text' => array('label'=>'Link Text', 'type'=>'text'),
            'button-class' => array('label'=>'Link Type', 'type'=>'toggle', 'default'=>'button', 
                'toggles'=>array(
                    'button' => 'Button',
                    'link' => 'Link',
                )),
//            'limit' => array('label'=>'Number of Items', 'type'=>'text', 'size'=>'small'),
            ),
        );
        
    $sections['ciniki.events.past'] = array(
        'name' => 'Past',
        'module' => 'Events',
        'settings' => array(
            'title' => array('label'=>'Title', 'type'=>'text'),
            'include-current' => array('label'=>'Include Current Events', 'type'=>'toggle', 'default'=>'no', 'toggles'=>array(
                'no' => 'No',
                'yes' => 'Yes',
                )),
            'image-position'=>array('label'=>'Image Position', 'type'=>'select', 'default'=>'top-right', 'options'=>array(
                'top-left' => 'Top Left',
                'top-left-inline' => 'Top Left Inline',
                'bottom-left' => 'Bottom Left',
                'top-right' => 'Top Right',
                'top-right-inline' => 'Top Right Inline',
                'bottom-right' => 'Bottom Right',
                )),
            'image-size'=>array('label'=>'Image Size', 'type'=>'toggle', 'default'=>'half', 'toggles'=>array(
                'half' => 'Full',
                'large' => 'Large',
                'medium' => 'Medium',
                'small' => 'Small',
                'tiny' => 'Tiny',
                )),
            'button-text' => array('label'=>'Link Text', 'type'=>'text'),
            'button-class' => array('label'=>'Link Type', 'type'=>'toggle', 'default'=>'button', 
                'toggles'=>array(
                    'button' => 'Button',
                    'link' => 'Link',
                )),
            ),
        );

    return array('stat'=>'ok', 'sections'=>$sections);
}
?>
