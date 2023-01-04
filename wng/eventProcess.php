<?php
//
// Description
// -----------
// This function will process a wng request for the events module.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_events_wng_eventProcess(&$ciniki, $tnid, $request, $section) {

    if( !isset($ciniki['tenant']['modules']['ciniki.events']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.events.105', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Make sure a valid section was passed
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.106', 'msg'=>"No event specified"));
    }
    $s = $section['settings'];
    $blocks = array();

    $event_permalink = $request['uri_split'][$request['cur_uri_pos']];

    //
    // Load the event
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'events', 'wng', 'eventLoad');
    $rc = ciniki_events_wng_eventLoad($ciniki, $tnid, $request, $event_permalink);
    if( $rc['stat'] != 'ok' || !isset($rc['event']) ) {
        $blocks[] = array(
            'type' => 'msg',
            'level' => 'error',
            'content' => 'Event does not exist',
            );
        return array('stat'=>'ok', 'blocks'=>$blocks);
    }
    $event = $rc['event'];

    $content = $event['description'] != '' ? $event['description'] : $event['synopsis'];

    //
    // Add links
    //
    if( isset($event['links']) && count($event['links']) > 0 ) {
        $content .= "\n\n";
        foreach($event['links'] as $link) {
            if( $link['url'] != '' ) {
                if( isset($link['url'][0]) && $link['url'][0] != '/' ) {
                    $content .= "<a class='link' target='_blank' href='{$link['url']}'>"
                        . ($link['name'] != '' ? $link['name'] : $link['url'])
                        . "</a>\n";
                } else {
                    $content .= "<a class='link' target='_blank' href='{$request['ssl_domain_base_url']}{$link['url']}'>"
                        . ($link['name'] != '' ? $link['name'] : $link['url'])
                        . "</a>\n";
                }
            }
        }
    }

    //
    // Add files
    //
    if( isset($event['files']) && count($event['files']) > 0 ) {
        $content .= "\n\n";
        foreach($event['files'] as $file) {
            if( $file['permalink'] != '' ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'wng', 'private', 'urlProcess');
                $rc = ciniki_wng_urlProcess($ciniki, $tnid, $request, 0, $request['page']['path'] . '/' . $event['permalink'] . '/file/' . $file['permalink']);
                if( $rc['stat'] == 'ok' ) {
                    $content .= "<a class='link' target='_blank' href='{$rc['url']}'>{$file['name']}</a>\n";
                }
            }
        }
    }

    //
    // Check if image selected
    //
    if( isset($request['uri_split'][($request['cur_uri_pos'] + 2)])
        && $request['uri_split'][($request['cur_uri_pos'] + 1)] == 'gallery'
        && $request['uri_split'][($request['cur_uri_pos'] + 2)] != ''
        && isset($event['images'][$request['uri_split'][($request['cur_uri_pos'] + 2)]])  // Check requested image exists
        ) {
        $image = $event['images'][$request['uri_split'][($request['cur_uri_pos'] + 2)]];
        $blocks[] = array(
            'type' => 'image',
            'title' => $event['name'] . ($image['title'] != '' ? ' - ' . $image['title'] : ''),
            'image-id' => $image['image-id'],
            'image-list' => $event['images'],
            'image-permalink' => $image['permalink'],
            'base-url' => $request['page']['path'] . '/' . $event['permalink'] . '/gallery',
            );
        return array('stat'=>'ok', 'clear'=>'yes', 'last'=>'yes', 'blocks'=>$blocks);
    }
    elseif( isset($request['uri_split'][($request['cur_uri_pos'] + 2)])
        && $request['uri_split'][($request['cur_uri_pos'] + 1)] == 'file'
        && $request['uri_split'][($request['cur_uri_pos'] + 2)] != ''
        && isset($event['files'][$request['uri_split'][($request['cur_uri_pos'] + 2)]])  // Check requested file exists
        ) {
        $file_permalink = $request['uri_split'][($request['cur_uri_pos'] + 2)];

        //
        // Generate download
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'blog', 'web', 'fileDownload');
        $rc = ciniki_blog_web_fileDownload($ciniki, $tnid, $event_permalink, $file_permalink, '');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.blog.98', 'msg'=>'Unable to download file', 'err'=>$rc['err']));
        }
        $file = $rc['file'];

        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        $file = $rc['file'];
        if( $file['extension'] == 'pdf' ) {
            header('Content-Type: application/pdf');
        }
        header('Content-Length: ' . strlen($file['binary_content']));
        header('Cache-Control: max-age=0');

        print $file['binary_content'];
        return array('stat'=>'exit');
    }
    else {
        $dates = $event['start_date'];
        if( $event['end_date'] != '' && $event['end_date'] != $event['start_date'] ) {
            $dates .= ($dates != '' ? ' - ' : '') . $event['end_date'];
        }
        if( $event['times'] != '' ) {
            $dates .= ($dates != '' ? ' - ' : '') . $event['times'];
        }
        $blocks[] = array(
            'type' => 'title', 
            'title' => $event['name'],
            'subtitle' => $dates,
            );
        if( $event['image_id'] > 0 ) {
            $blocks[] = array(
                'type' => 'asideimage',
                'image-id' => $event['image_id'],
                );
        }
        $blocks[] = array(
            'type' => 'text',
            'content' => $content,
            );

    }

    //
    // Check if prices specified
    //
    if( isset($event['prices']) && count($event['prices']) > 0 && $section['ref'] != 'ciniki.events.past' ) {
        $blocks[] = array(
            'type' => 'pricelist',
            'prices' => $event['prices'],
            );
    }


    //
    // Check if images
    //
    if( isset($event['images']) && count($event['images']) > 0 ) {
        foreach($event['images'] as $iid => $image) {
            $event['images'][$iid]['url'] = $request['page']['path'] . '/' . $event['permalink'] . '/gallery/' . $image['permalink'];
        }
        $blocks[] = array(
            'type' => 'gallery',
            'title' => 'Additional Images',
            'class' => 'limit-width',
            'items' => $event['images'],
            );
    }

    return array('stat'=>'ok', 'blocks'=>$blocks, 'stop'=>'yes', 'clear'=>'yes');
}
?>
