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
            'unit_donation_amount':{'label':'Donation Portion', 'type':'text', 'size':'small',
                'visible':function() {return M.modFlagSet('ciniki.sapos', 0x04000000);},
                },
            'taxtype_id':{'label':'Taxes', 'active':'no', 'type':'select', 'options':{}},
            'webflags':{'label':'Web', 'type':'flags', 'toggle':'no', 'join':'yes', 'flags':{}},
            'webflags2':{'label':'Individual Ticket', 'type':'flagtoggle', 'bit':0x02, 'field':'webflags', 'default':'no',
                'visible':function() {return M.modFlagSet('ciniki.events', 0x08);},
                },
            'webflags3':{'label':'Sold Out', 'type':'flagtoggle', 'bit':0x04, 'field':'webflags', 'default':'no',
                'visible':function() {return M.modFlagSet('ciniki.events', 0x08);},
                },
            'webflags8':{'label':'Limited Number', 'type':'flagtoggle', 'bit':0x80, 'field':'webflags', 'default':'no',
                'visible':function() {return M.modFlagSet('ciniki.events', 0x01);},
                'on_fields':['num_tickets'],
                },
            'num_tickets':{'label':'Number of Tickets', 'type':'text', 'size':'small', 'visible':'no'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_events_prices.edit.save();'},
            'delete':{'label':'Delete', 'fn':'M.ciniki_events_prices.edit.remove();'},
            }},
        };  
    this.edit.fieldValue = function(s, i, d) { return this.data[i]; }
    this.edit.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.events.priceHistory', 'args':{'tnid':M.curTenantID, 'price_id':this.price_id, 'event_id':this.event_id, 'field':i}};
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
            M.api.getJSONCb('ciniki.events.priceGet', {'tnid':M.curTenantID, 'price_id':this.price_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_events_prices.edit;
                p.data = rsp.price;
                p.sections.price.fields.num_tickets.visible = ((p.data.webflags&0x80) > 0 ? 'yes' : 'no');
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
                M.api.postJSONCb('ciniki.events.priceUpdate', {'tnid':M.curTenantID, 'price_id':M.ciniki_events_prices.edit.price_id}, c, function(rsp) {
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
            M.api.postJSONCb('ciniki.events.priceAdd', {'tnid':M.curTenantID, 'event_id':this.event_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                M.ciniki_events_prices.edit.close();
            });
        }
    };
    this.edit.remove = function() {
        M.confirm("Are you sure you want to remove this price?",null,function() {
            M.api.getJSONCb('ciniki.events.priceDelete', {'tnid':M.curTenantID, 'price_id':M.ciniki_events_prices.edit.price_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_events_prices.edit.close();    
            });
        });
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
            M.alert('App Error');
            return false;
        } 

        //
        // Setup the tax types
        //
        if( M.curTenant.modules['ciniki.taxes'] != null ) {
            this.edit.sections.price.fields.taxtype_id.active = 'yes';
            this.edit.sections.price.fields.taxtype_id.options = {'0':'No Taxes'};
            if( M.curTenant.taxes != null && M.curTenant.taxes.settings.types != null ) {
                for(i in M.curTenant.taxes.settings.types) {
                    this.edit.sections.price.fields.taxtype_id.options[M.curTenant.taxes.settings.types[i].type.id] = M.curTenant.taxes.settings.types[i].type.name;
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
        if( (M.curTenant.modules['ciniki.customers'].flags&0x02) > 0 ) {
            this.edit.sections.price.fields.available_to.flags['6'] = {'name':'Members'};
            this.edit.sections.price.fields.webflags.flags['6'] = {'name':'Show Members Price'};
        }
        if( (M.curTenant.modules['ciniki.customers'].flags&0x10) > 0 ) {
            this.edit.sections.price.fields.available_to.flags['7'] = {'name':'Dealers'};
            this.edit.sections.price.fields.webflags.flags['7'] = {'name':'Show Dealers Price'};
        }
        if( (M.curTenant.modules['ciniki.customers'].flags&0x100) > 0 ) {
            this.edit.sections.price.fields.available_to.flags['8'] = {'name':'Distributors'};
            this.edit.sections.price.fields.webflags.flags['8'] = {'name':'Show Distributors Price'};
        }
        this.edit.open(cb, args.price_id, args.event_id);
    }
}
