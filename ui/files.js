//
// The events app to manage the events for the business
//
function ciniki_events_files() {
    this.init = function() {
        //
        // The panel to display the add form
        //
        this.add = new M.panel('Add File',
            'ciniki_events_files', 'add',
            'mc', 'medium', 'sectioned', 'ciniki.events.info.edit');
        this.add.default_data = {'type':'20'};
        this.add.data = {}; 
        this.add.sections = {
            '_file':{'label':'File', 
                'gstep':1,
                'gtitle':'Select the file to upload',
                'gtext':'Press the button below to select a file from your computer to table.',
                'fields':{
                    'uploadfile':{'label':'', 'type':'file', 'hidelabel':'yes'},
                }},
            'info':{'label':'Information', 'type':'simpleform', 
                'gstep':1,
                'fields':{
                    'name':{'label':'Name', 'type':'text',
                        'gtitle':'What is the name of the file?',
                        'htext':"The name you want to display on your website. Examples: Registration Form, Event Schedule, etc.",
                        },
                }},
            '_buttons':{'label':'', 
                'gstep':1,
                'gtitle':'Save the file',
                'buttons':{
                    'save':{'label':'Save', 'fn':'M.ciniki_events_files.addFile();'},
                }},
        };
        this.add.fieldValue = function(s, i, d) { 
            if( this.data[i] != null ) {
                return this.data[i]; 
            } 
            return ''; 
        };
        this.add.addButton('save', 'Save', 'M.ciniki_events_files.addFile();');
        this.add.addClose('Cancel');

        //
        // The panel to display the edit form
        //
        this.edit = new M.panel('File',
            'ciniki_events_files', 'edit',
            'mc', 'medium', 'sectioned', 'ciniki.events.info.edit');
        this.edit.file_id = 0;
        this.edit.data = null;
        this.edit.sections = {
            'info':{'label':'Details', 'type':'simpleform', 
                'gstep':1,
                'gtitle':'File Details',
                'gtext':'If you want to change the file, you need to delete this one, and add the new one.',
                'fields':{
                    'name':{'label':'Name', 'type':'text',
                        'gtitle':'Would you like to change the name?',
                        'htext':"The name you want to display on your website. Examples: Registration Form, Event Schedule, etc.",
                        },
                }},
            '_buttons':{'label':'', 
                'gstep':1,
                'gmore':'If you want to save the original file to your computer, press <em>Download</em>.<br/>'
                    + 'If you want to remove the file, press <em>Delete</em>.',
                'buttons':{
                    'save':{'label':'Save', 'fn':'M.ciniki_events_files.saveFile();'},
                    'download':{'label':'Download', 'fn':'M.ciniki_events_files.downloadFile(M.ciniki_events_files.edit.file_id);'},
                    'delete':{'label':'Delete', 'fn':'M.ciniki_events_files.deleteFile();'},
                }},
        };
        this.edit.fieldValue = function(s, i, d) { 
            return this.data[i]; 
        }
        this.edit.sectionData = function(s) {
            return this.data[s];
        };
        this.edit.fieldHistoryArgs = function(s, i) {
            return {'method':'ciniki.events.fileHistory', 'args':{'business_id':M.curBusinessID, 
                'file_id':this.file_id, 'field':i}};
        };
        this.edit.addButton('save', 'Save', 'M.ciniki_events_files.saveFile();');
        this.edit.addClose('Cancel');
    }

    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) {
            args = eval(aG);
        }

        //
        // Create container
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_events_files', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        }

        if( args.file_id != null && args.file_id > 0 ) {
            this.showEditFile(cb, args.file_id);
        } else if( args.event_id != null && args.event_id > 0 && args.add != null && args.add == 'yes' ) {
            this.showAddFile(cb, args.event_id);
        } else {
            alert('Invalid request');
        }
    }

    this.showMenu = function(cb) {
        this.menu.refresh();
        this.menu.show(cb);
    };

    this.showAddFile = function(cb, eid) {
        this.add.reset();
        this.add.data = {'name':''};
        this.add.file_id = 0;
        this.add.event_id = eid;
        this.add.refresh();
        this.add.show(cb);
    };

    this.addFile = function() {
        var c = this.add.serializeFormData('yes');

        if( c != '' ) {
            var rsp = M.api.postJSONFormData('ciniki.events.fileAdd', 
                {'business_id':M.curBusinessID, 'event_id':M.ciniki_events_files.add.event_id}, c,
                    function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } else {
                            M.ciniki_events_files.add.file_id = rsp.id;
                            M.ciniki_events_files.add.close();
                        }
                    });
        } else {
            M.ciniki_events_files.add.close();
        }
    };

    this.showEditFile = function(cb, fid) {
        if( fid != null ) {
            this.edit.file_id = fid;
        }
        var rsp = M.api.getJSONCb('ciniki.events.fileGet', {'business_id':M.curBusinessID, 
            'file_id':this.edit.file_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_events_files.edit.data = rsp.file;
                M.ciniki_events_files.edit.refresh();
                M.ciniki_events_files.edit.show(cb);
            });
    };

    this.saveFile = function() {
        var c = this.edit.serializeFormData('no');

        if( c != '' ) {
            var rsp = M.api.postJSONFormData('ciniki.events.fileUpdate', 
                {'business_id':M.curBusinessID, 'file_id':this.edit.file_id}, c,
                    function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } else {
                            M.ciniki_events_files.edit.close();
                        }
                    });
        }
    };

    this.deleteFile = function() {
        if( confirm('Are you sure you want to delete \'' + this.edit.data.name + '\'?  All information about it will be removed and unrecoverable.') ) {
            var rsp = M.api.getJSONCb('ciniki.events.fileDelete', {'business_id':M.curBusinessID, 
                'file_id':M.ciniki_events_files.edit.file_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_events_files.edit.close();
                });
        }
    };

    this.downloadFile = function(fid) {
        M.api.openFile('ciniki.events.fileDownload', {'business_id':M.curBusinessID, 'file_id':fid});
    };
}
