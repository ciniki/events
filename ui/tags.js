//
function ciniki_events_tags() {
    //
    // Panels
    //
    this.toggleOptions = {'no':'Off', 'yes':'On'};

    //
    // The tags list panel
    //
    this.menu = new M.panel('Settings', 'ciniki_events_tags', 'menu', 'mc', 'narrow', 'sectioned', 'ciniki.events.tags.menu');
    this.menu.sections = {};
    this.menu.sectionData = function(s) {
        if( this.data[s] != null ) { return this.data[s]; }
        return '';
    };
    this.menu.cellValue = function(s, i, j, d) {
        return d.tag.name;
    };
    this.menu.rowFn = function(s, i, d) {
        return 'M.ciniki_events_tags.tag.open(\'M.ciniki_events_tags.menu.open();\',\'' + d.tag.type + '\',\'' + escape(d.tag.name) + '\');';
    };
    this.menu.open = function(cb) {
        M.api.getJSONCb('ciniki.events.tagList', {'tnid':M.curTenantID, 'tag_type':'10'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_events_tags.menu;
            p.data = {'tags':rsp.tags};
            p.sections = {'tags':{
                'label':'Categories', 'type':'simplegrid', 'num_cols':1,
                }};
            p.refresh();
            p.show(cb);
        });
    }
    this.menu.addClose('Back');

    //
    // The edit tag panel
    //
    this.tag = new M.panel('Tag', 'ciniki_events_tags', 'tag', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.events.tags.tag');
    this.tag.tag_type = 10;
    this.tag.tag_permalink = '';
    this.tag.sections = {
        '_image':{'label':'', 'aside':'yes', 'type':'imageform', 'fields':{
            'image-id':{'label':'', 'hidelabel':'yes', 'type':'image_id', 'controls':'all', 'history':'no'},
            }},
        '_synopsis':{'label':'Synopsis', 'fields':{
            'synopsis':{'label':'', 'hidelabel':'', 'type':'textarea', 'size':'small'},
            }},
        '_content':{'label':'Description', 'type':'simpleform', 'fields':{
            'content':{'label':'', 'type':'textarea', 'size':'large', 'hidelabel':'yes'},
        }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_events_tags.tag.save();'},
        }},
    };
    this.tag.fieldValue = function(s, i, d) { 
        return this.data[i]; 
    }
    this.tag.addDropImage = function(iid) {
        M.ciniki_events_tags.tag.setFieldValue('image-id', iid, null, null);
        return true;
    };
    this.tag.deleteImage = function(fid) {
        this.setFieldValue(fid, 0, null, null);
        return true;
    };
    this.tag.open = function(cb, type, permalink) {
        if( type != null ) { this.tag_type = type; }
        if( permalink != null ) { this.tag_permalink = permalink; }
        M.api.getJSONCb('ciniki.events.tagGet', {'tnid':M.curTenantID, 'tag_type':this.tag_type, 'tag_permalink':this.tag_permalink}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_events_tags.tag;
            p.data = rsp.details;
            p.refresh();
            p.show(cb);
        }); 
    }
    this.tag.save = function() {
        var c = this.serializeFormData('no');
        if( c != '' ) {
            M.api.postJSONFormData('ciniki.events.tagUpdate', {'tnid':M.curTenantID, 'tag_type':this.tag_type, 'tag_permalink':this.tag_permalink}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                M.ciniki_events_tags.tag.close();
            });
        } else {
            this.close();
        }
    }
    this.tag.addButton('save', 'Save', 'M.ciniki_events_tags.tag.save();');
    this.tag.addClose('Cancel');

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
        var appContainer = M.createContainer(appPrefix, 'ciniki_events_tags', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        } 

        if( args.tag_type != null && args.tag_permalink != null ) {
            this.tag.open(cb, args.tag_type, args.tag_permalink);
        } else {
            this.menu.open(cb);
        }
    }
}
