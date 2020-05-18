//
// The app to add/edit events event images
//
function ciniki_events_images() {
    this.webFlags = {
        '1':{'name':'Hidden'},
        };
    //
    // The panel to display the edit form
    //
    this.edit = new M.panel('Edit Image', 'ciniki_events_images', 'edit', 'mc', 'medium', 'sectioned', 'ciniki.events.images.edit');
    this.edit.default_data = {};
    this.edit.data = {};
    this.edit.event_id = 0;
    this.edit.sections = {
        '_image':{'label':'Image', 'type':'imageform', 'fields':{
            'image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
            }},
        'info':{'label':'Information', 'type':'simpleform', 'fields':{
            'name':{'label':'Title', 'type':'text', },
            'webflags':{'label':'Website', 'type':'flags', 'join':'yes', 'flags':this.webFlags},
            }},
        '_description':{'label':'Description', 'type':'simpleform', 'fields':{
            'description':{'label':'', 'type':'textarea', 'size':'medium', 'hidelabel':'yes'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_events_images.edit.save();'},
            'delete':{'label':'Delete', 'visible':'no', 'fn':'M.ciniki_events_images.edit.remove();'},
            }},
    };
    this.edit.fieldValue = function(s, i, d) { 
        if( this.data[i] != null ) {
            return this.data[i]; 
        } 
        return ''; 
    };
    this.edit.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.events.imageHistory', 'args':{'tnid':M.curTenantID, 'event_image_id':this.event_image_id, 'field':i}};
    };
    this.edit.addDropImage = function(iid) {
        M.ciniki_events_images.edit.setFieldValue('image_id', iid, null, null);
        return true;
    };
    this.edit.open = function(cb, iid, eid) {
        if( iid != null ) { this.event_image_id = iid; }
        if( eid != null ) { this.event_id = eid; }
        if( this.event_image_id > 0 ) {
            this.reset();
            this.sections._buttons.buttons.delete.visible = 'yes';
            M.api.getJSONCb('ciniki.events.imageGet', {'tnid':M.curTenantID, 'event_image_id':this.event_image_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_events_images.edit;
                p.data = rsp.image;
                p.refresh();
                p.show(cb);
            });
        } else {
            this.reset();
            this.sections._buttons.buttons.delete.visible = 'no';
            this.data = {};
            this.refresh();
            this.show(cb);
        }
    };
    this.edit.save = function() {
        if( this.event_image_id > 0 ) {
            var c = this.serializeFormData('no');
            if( c != '' ) {
                M.api.postJSONFormData('ciniki.events.imageUpdate', {'tnid':M.curTenantID, 'event_image_id':this.event_image_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } else {
                        M.ciniki_events_images.edit.close();
                    }
                });
            } else {
                this.close();
            }
        } else {
            var c = this.serializeFormData('yes');
            M.api.postJSONFormData('ciniki.events.imageAdd', {'tnid':M.curTenantID, 'event_id':this.event_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } else {
                    M.ciniki_events_images.edit.close();
                }
            });
        }
    };
    this.edit.remove = function() {
        M.confirm('Are you sure you want to delete this image?',null,function() {
            M.api.getJSONCb('ciniki.events.imageDelete', {'tnid':M.curTenantID, 'event_image_id':M.ciniki_events_images.edit.event_image_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_events_images.edit.close();
            });
        });
    };
    this.edit.addButton('save', 'Save', 'M.ciniki_events_images.edit.save();');
    this.edit.addClose('Cancel');

    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) { args = eval(aG); }

        //
        // Create container
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_events_images', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        }

        if( args.add != null && args.add == 'yes' ) {
            this.edit.open(cb, 0, args.event_id);
        } else if( args.event_image_id != null && args.event_image_id > 0 ) {
            this.edit.open(cb, args.event_image_id);
        }
        return false;
    }
}
