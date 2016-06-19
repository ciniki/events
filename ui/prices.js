//
// This app will display and update prices for an event
//
function ciniki_events_prices() {
    //
    // The panel for editing a registrant
    //
    this.edit = new M.panel('Event Price', 'ciniki_events_prices', 'edit', 'mc', 'medium', 'sectioned', 'ciniki.events.prices.edit');
    this.edit.data = null;
    this.edit.event_id = 0;
    this.edit.price_id = 0;
    this.edit.sections = { 
        'price':{'label':'Price', 'fields':{
            'name':{'label':'Name', 'type':'text'},
            'available_to':{'label':'Available', 'type':'flags', 'default':'1', 'flags':{}},
//          'valid_from':{'label':'Valid From', 'hint':'', 'type':'text'},
//          'valid_to':{'label':'Valid To', 'hint':'', 'type':'text'},
            'unit_amount':{'label':'Unit Amount', 'type':'text', 'size':'small'},
            'unit_discount_amount':{'label':'Discount Amount', 'type':'text', 'size':'small'},
            'unit_discount_percentage':{'label':'Discount Percent', 'type':'text', 'size':'small'},
            'taxtype_id':{'label':'Taxes', 'active':'no', 'type':'select', 'options':{}},
            'webflags':{'label':'Web', 'type':'flags', 'toggle':'no', 'join':'yes', 'flags':{}},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_events_prices.edit.save();'},
            'delete':{'label':'Delete', 'fn':'M.ciniki_events_prices.edit.remove();'},
            }},
        };  
    this.edit.fieldValue = function(s, i, d) { return this.data[i]; }
    this.edit.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.events.priceHistory', 'args':{'business_id':M.curBusinessID, 'price_id':this.price_id, 'event_id':this.event_id, 'field':i}};
    }
    this.edit.sectionData = function(s) {
        return this.data[s];
    }
    this.edit.rowFn = function(s, i, d) { return ''; }
    this.edit.open = function(cb, pid, eid) {
        this.reset();
        if( pid != null ) { this.price_id = pid; }
        if( eid != null ) { this.event_id = eid; }

        // Check if this is editing a existing price or adding a new one
        if( this.price_id > 0 ) {
            this.sections._buttons.buttons.delete.visible = 'yes';
            M.api.getJSONCb('ciniki.events.priceGet', {'business_id':M.curBusinessID, 'price_id':this.price_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_events_prices.edit;
                p.data = rsp.price;
                p.event_id = rsp.price.event_id;
                p.refresh();
                p.show(cb);
            });
        } else {
            this.sections._buttons.buttons.delete.visible = 'no';
            this.data = {};
            this.refresh();
            this.show(cb);
        }
    };
    this.edit.save = function() {
        if( this.price_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.events.priceUpdate', {'business_id':M.curBusinessID, 'price_id':M.ciniki_events_prices.edit.price_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_events_prices.edit.close();
                });
            } else {
                this.close();
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.events.priceAdd', {'business_id':M.curBusinessID, 'event_id':this.event_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                M.ciniki_events_prices.edit.close();
            });
        }
    };
    this.edit.remove = function() {
        if( confirm("Are you sure you want to remove this price?") ) {
            M.api.getJSONCb('ciniki.events.priceDelete', {'business_id':M.curBusinessID, 'price_id':this.price_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_events_prices.edit.close();    
            });
        }
    };
    this.edit.addButton('save', 'Save', 'M.ciniki_events_prices.edit.save();');
    this.edit.addClose('Cancel');

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
        var appContainer = M.createContainer(appPrefix, 'ciniki_events_prices', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        } 

        //
        // Setup the tax types
        //
        if( M.curBusiness.modules['ciniki.taxes'] != null ) {
            this.edit.sections.price.fields.taxtype_id.active = 'yes';
            this.edit.sections.price.fields.taxtype_id.options = {'0':'No Taxes'};
            if( M.curBusiness.taxes != null && M.curBusiness.taxes.settings.types != null ) {
                for(i in M.curBusiness.taxes.settings.types) {
                    this.edit.sections.price.fields.taxtype_id.options[M.curBusiness.taxes.settings.types[i].type.id] = M.curBusiness.taxes.settings.types[i].type.name;
                }
            }
        } else {
            this.edit.sections.price.fields.taxtype_id.active = 'no';
            this.edit.sections.price.fields.taxtype_id.options = {'0':'No Taxes'};
        }
        
        //
        // Setup the available_to flags and webflags
        //
        this.edit.sections.price.fields.available_to.flags = {'1':{'name':'Public'}};
        this.edit.sections.price.fields.webflags.flags = {'1':{'name':'Hidden'}};
        if( (M.curBusiness.modules['ciniki.customers'].flags&0x02) > 0 ) {
            this.edit.sections.price.fields.available_to.flags['6'] = {'name':'Members'};
            this.edit.sections.price.fields.webflags.flags['6'] = {'name':'Show Members Price'};
        }
        if( (M.curBusiness.modules['ciniki.customers'].flags&0x10) > 0 ) {
            this.edit.sections.price.fields.available_to.flags['7'] = {'name':'Dealers'};
            this.edit.sections.price.fields.webflags.flags['7'] = {'name':'Show Dealers Price'};
        }
        if( (M.curBusiness.modules['ciniki.customers'].flags&0x100) > 0 ) {
            this.edit.sections.price.fields.available_to.flags['8'] = {'name':'Distributors'};
            this.edit.sections.price.fields.webflags.flags['8'] = {'name':'Show Distributors Price'};
        }
        this.edit.open(cb, args.price_id, args.event_id);
    }
}
