//
function ciniki_events_tags() {
    //
    // Panels
    //
    this.toggleOptions = {'no':'Off', 'yes':'On'};

    this.init = function() {
        //
        // The tags list panel
        //
        this.menu = new M.panel('Settings',
            'ciniki_events_tags', 'menu',
            'mc', 'narrow', 'sectioned', 'ciniki.events.tags.menu');
        this.menu.sections = {};
        this.menu.sectionData = function(s) {
            if( this.data[s] != null ) { return this.data[s]; }
            return '';
        };
        this.menu.cellValue = function(s, i, j, d) {
            return d.tag.name;
        };
        this.menu.rowFn = function(s, i, d) {
            return 'M.ciniki_events_tags.editTag(\'M.ciniki_events_tags.showMenu();\',\'' + d.tag.type + '\',\'' + escape(d.tag.name) + '\');';
        };
        this.menu.addClose('Back');

        //
        // The edit tag panel
        //
        this.tag = new M.panel('Tag',
            'ciniki_events_tags', 'tag',
            'mc', 'medium mediumaside', 'sectioned', 'ciniki.events.tags.tag');
        this.tag.tag_type = 10;
        this.tag.tag_permalink = '';
        this.tag.sections = {
            '_image':{'label':'', 'aside':'yes', 'fields':{'type':'imageform',
                'image-id':{'label':'', 'hidelabel':'yes', 'type':'image_id', 'controls':'all', 'history':'no'},
                }},
            '_synopsis':{'label':'Synopsis', 'fields':{
                'synopsis':{'label':'', 'hidelabel':'', 'type':'textarea', 'size':'small'},
                }},
            '_content':{'label':'Description', 'type':'simpleform', 'fields':{
                'content':{'label':'', 'type':'textarea', 'size':'large', 'hidelabel':'yes'},
            }},
            '_buttons':{'label':'', 'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_events_tags.saveTag();'},
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
        this.tag.addButton('save', 'Save', 'M.ciniki_events_tags.saveTag();');
        this.tag.addClose('Cancel');
    }

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
            alert('App Error');
            return false;
        } 

        if( args.tag_type != null && args.tag_permalink != null ) {
            this.editTag(cb, args.tag_type, args.tag_permalink);
        } else {
            this.showMenu(cb);
        }
    }

    //
    // Grab the stats for the business from the database and present the list of orders.
    //
    this.showMenu = function(cb) {
        M.api.getJSONCb('ciniki.events.tagList', {'business_id':M.curBusinessID, 'tag_type':'10'}, function(rsp) {
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

    this.editTag = function(cb, type, permalink) {
        if( type != null ) { this.tag.tag_type = type; }
//      if( name != null ) { this.tag.title = unescape(name); }
        if( permalink != null ) { this.tag.tag_permalink = permalink; }
        M.api.getJSONCb('ciniki.events.tagGet', {'business_id':M.curBusinessID,
            'tag_type':this.tag.tag_type, 'tag_permalink':this.tag.tag_permalink}, function(rsp) {
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

    this.saveTag = function() {
        var c = this.tag.serializeFormData('no');
        if( c != '' ) {
            M.api.postJSONFormData('ciniki.events.tagUpdate', 
                {'business_id':M.curBusinessID, 'tag_type':this.tag.tag_type,
                'tag_permalink':this.tag.tag_permalink}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_events_tags.tag.close();
                });
        } else {
            M.ciniki_events_tags.tag.close();
        }
    }
}
