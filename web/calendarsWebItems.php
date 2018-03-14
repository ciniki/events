<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_events_web_calendarsWebItems($ciniki, $settings, $tnid, $args) {

    if( !isset($args['ltz_start']) || !is_a($args['ltz_start'], 'DateTime') ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.65', 'msg'=>'Invalid start date'));
    }
    if( !isset($args['ltz_end']) || !is_a($args['ltz_end'], 'DateTime') ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.66', 'msg'=>'Invalid end date'));
    }

    $sdt = $args['ltz_start'];
    $edt = $args['ltz_end'];

    if( isset($ciniki['tenant']['module_pages']['ciniki.events']['base_url']) ) {
        $base_url = $ciniki['tenant']['module_pages']['ciniki.events']['base_url'];
    } elseif( isset($ciniki['tenant']['module_pages']['ciniki.events.upcoming']['base_url']) ) {
        $base_url = $ciniki['tenant']['module_pages']['ciniki.events.upcoming']['base_url'];
    } else {
        $base_url = '/events';
    }

    //
    // Check if this modules items are to be included in the calendar
    //
    if( isset($settings['ciniki-events-calendar-include']) && $settings['ciniki-events-calendar-include'] == 'no' ) {
        return array('stat'=>'ok');
    }

    //
    // Check if colours specified
    //
    $style = '';
    if( isset($settings['ciniki-events-colour-background']) && $settings['ciniki-events-colour-background'] != '' ) {
        $style .= ($style != '' ? ' ':'') . 'background: ' . $settings['ciniki-events-colour-background'] . ';';
    }
    if( isset($settings['ciniki-events-colour-border']) && $settings['ciniki-events-colour-border'] != '' ) {
        $style .= ($style != '' ? ' ':'') . ' border: 1px solid ' . $settings['ciniki-events-colour-border'] . ';';
    }
    if( isset($settings['ciniki-events-colour-font']) && $settings['ciniki-events-colour-font'] != '' ) {
        $style .= ($style != '' ? ' ':'') . ' color: ' . $settings['ciniki-events-colour-font'] . ';';
    }

    //
    // Setup the legend
    //
    if( isset($settings['ciniki-events-legend-title']) && $settings['ciniki-events-legend-title'] != '' ) {
        $legend = array(
            array('title'=>$settings['ciniki-events-legend-title'], 'style'=>$style)
            );
    } else {
        $legend = array();
    }

    //
    // FIXME: Add the ability to select the tags for an event and turn tags into classes
    //

    //
    // Get the list of events between the start and end date specified
    //
    $strsql = "SELECT ciniki_events.id, "
        . "ciniki_events.name, "
        . "ciniki_events.permalink, "
        . "ciniki_events.url, "
        . "IF(ciniki_events.long_description='', 'no', 'yes') AS isdetails, "
        . "DATE_FORMAT(ciniki_events.start_date, '%Y-%m-%d') AS start_date, "
        . "IF(ciniki_events.end_date < start_date, '', DATE_FORMAT(ciniki_events.end_date, '%Y-%m-%d')) AS end_date, "
        . "ciniki_events.times, "
        . "ciniki_events.description, "
        . "ciniki_events.primary_image_id "
        . "FROM ciniki_events "
        . "WHERE ciniki_events.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND (ciniki_events.flags&0x01) = 0x01 "
        // Event has to start or end between the dates for the calendar
        . "AND (("
            . "ciniki_events.start_date >= '" . ciniki_core_dbQuote($ciniki, $sdt->format('Y-m-d')) . "' "
            . "AND ciniki_events.start_date <= '" . ciniki_core_dbQuote($ciniki, $edt->format('Y-m-d')) . "' "
            . ") "
            . "OR ("
            . "ciniki_events.end_date >= '" . ciniki_core_dbQuote($ciniki, $sdt->format('Y-m-d')) . "' "
            . "AND ciniki_events.end_date <= '" . ciniki_core_dbQuote($ciniki, $edt->format('Y-m-d')) . "' "
            . ")) "
        . "ORDER BY ciniki_events.start_date DESC, ciniki_events.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.events', array(
        array('container'=>'events', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'title'=>'name', 'image_id'=>'primary_image_id', 'isdetails', 
                'start_date', 'end_date', 'permalink', 'times', 'description', 'url')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $prefix = '';
    if( isset($settings['ciniki-events-prefix']) ) {
        $prefix = $settings['ciniki-events-prefix'];
    }

    $items = array();
    if( isset($rc['events']) ) {
        foreach($rc['events'] as $event) {
            $item = array(
                'title'=>$prefix . $event['title'],
                'time_text'=>'',
                'style'=>$style,
                'url'=>$base_url . '/' . $event['permalink'],
                'classes'=>array('events'),
                );
            if( isset($settings['ciniki-events-display-times']) && $settings['ciniki-events-display-times'] == 'yes' ) {
                $item['time_text'] = $event['times'];
            }
            if( $event['end_date'] != '' && $event['start_date'] != $event['end_date'] ) {
                //
                // Add an item to the items list for each date of the event
                //
                $dt = new DateTime($event['start_date'], $sdt->getTimezone());
                $c = 0;
                do {
                    if( $c > 365 ) {
                        error_log("ERR: runaway event dates " . $event['id']);
                        break;
                    }
                    $cur_date = $dt->format('Y-m-d');
                    if( !isset($items[$cur_date]) ) {
                        $items[$cur_date]['items'] = array();
                    }
                    $items[$cur_date]['items'][] = $item;

                    $dt->add(new DateInterval('P1D'));
                    $c++;
                } while( $cur_date != $event['end_date']);
            } else {
                if( !isset($items[$event['start_date']]) ) {
                    $items[$event['start_date']]['items'] = array();
                }
                $items[$event['start_date']]['items'][] = $item;
            }
        }
    }

    return array('stat'=>'ok', 'items'=>$items, 'legend'=>$legend);
}
?>
