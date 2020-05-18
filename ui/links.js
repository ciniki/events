//
function ciniki_events_links() {
    //
    // The panel to edit an existing link
    //
    this.edit = new M.panel('Link', 'ciniki_events_links', 'edit', 'mc', 'medium', 'sectioned', 'ciniki.event.links.edit');
    this.edit.data = {};
    this.edit.link_id = 0;
    this.edit.sections = {
        'link':{'label':'Link', 'fields':{
            'name':{'label':'Name', 'hint':'', 'type':'text'},
            'url':{'label':'URL', 'hint':'', 'type':'text' },
            }},
        '_description':{'label':'Additional Information', 'fields':{
            'description':{'label':'', 'hidelabel':'yes', 'hint':'', 'type':'textarea'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_events_links.edit.save();'},
            'delete':{'label':'Delete', 'fn':'M.ciniki_events_links.edit.remove();'},
            }},
        };
    this.edit.fieldValue = function(s, i, d) { return this.data[i]; }
    this.edit.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.events.linkHistory', 'args':{'tnid':M.curTenantID, 'link_id':this.link_id, 'field':i}};
    };
    this.edit.open = function(cb, pid, lid) {
        if( pid != null ) { this.event_id = pid; }
        if( lid != null ) { this.link_id = lid; }
        if( this.link_id > 0 ) {
            this.reset();
            this.sections._buttons.buttons.delete.visible = 'yes';
            M.api.getJSONCb('ciniki.events.linkGet', {'tnid':M.curTenantID, 'link_id':this.link_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_events_links.edit;
                p.data = rsp.link;
                p.refresh();
                p.show(cb);
            });
        } else {
            this.reset();
            this.data = {};
            this.sections._buttons.buttons.delete.visible = 'no';
            this.refresh();
            this.show(cb);
        }
    };
    this.edit.save = function() {
        if( this.link_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.events.linkUpdate', {'tnid':M.curTenantID, 'link_id':this.link_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_events_links.edit.close();
                });
            } else {
                this.close();
            }
        } else {
            var c = this.serializeForm('yes');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.events.linkAdd', {'tnid':M.curTenantID, 'event_id':this.event_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_events_links.edit.close();
                });
            } else {
                this.close();
            }
        }
    };
    this.edit.remove = function() {
        M.confirm("Are you sure you want to remove this link?",null,function() {
            M.api.getJSONCb('ciniki.events.linkDelete', {'tnid':M.curTenantID, 'link_id':M.ciniki_events_links.edit.link_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_events_links.edit.close();
            });
        });
    };
    this.edit.addButton('save', 'Save', 'M.ciniki_events_links.edit.save();');
    this.edit.addClose('cancel');

    //
    // Arguments:
    // aG - The arguments to be parsed into args
    //
    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) { args = eval(aG); }

        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_events_links', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        } 

        if( args.link_id != null && args.link_id > 0 ) {
            // Edit an existing link
            this.edit.open(cb, 0, args.link_id);
        } else if( args.event_id != null && args.event_id > 0 ) {
            // Add a new link for a event
            this.edit.open(cb, args.event_id, 0);
        }
    };
}
