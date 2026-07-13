@extends($layout)
@section('content')

@php
    $module = 'project';
    $moduleId = session('currentProject');
    $viewMode = $_GET['view'] ?? 'list';
    $currentFolderId = isset($_GET['folderId']) ? (int) $_GET['folderId'] : null;
@endphp

<div class="pageheader">
    <div class="pageicon"><span class="fa fa-fw fa-file"></span></div>
    <div class="pagetitle">
        <h5>{{ session('currentProjectName') }}</h5>
        <h1>Files</h1>
    </div>
</div>

<div class="maincontent">

<div id="fileManager" style="background:#fff; border:1px solid #ddd; border-radius:12px; margin-bottom:20px; overflow:hidden;">

    {{-- Toolbar --}}
    <div style="display:flex; align-items:center; gap:8px; padding:10px 14px; background:#f5f5f5; border-bottom:1px solid #ddd; flex-wrap:wrap;">
        <button onclick="leantime.fm.createFile()"
                style="padding:5px 12px; background:#fff; border:1px solid #ccc; border-radius:6px; font-size:13px; cursor:pointer; color:#333;">
            <i class="fa fa-file-text-o"></i> New File
        </button>
        <button onclick="leantime.fm.createFolder()"
                style="padding:5px 12px; background:#fff; border:1px solid #ccc; border-radius:6px; font-size:13px; cursor:pointer; color:#333;">
            <i class="fa fa-folder"></i> New Folder
        </button>
        <button onclick="jQuery('#fmUploadInput').click()"
                style="padding:5px 12px; background:#fff; border:1px solid #ccc; border-radius:6px; font-size:13px; cursor:pointer; color:#333;">
            <i class="fa fa-upload"></i> Upload
        </button>
        <input type="file" id="fmUploadInput" style="display:none" onchange="leantime.fm.uploadFile(this)" />

        <span style="color:#ccc; margin:0 4px;">|</span>

        <button class="fm-view-btn" data-view="list" onclick="leantime.fm.setView('list')"
                style="padding:5px 12px; border:1px solid {{ $viewMode==='list' ? '#337ab7' : '#ccc' }}; border-radius:6px; font-size:13px; cursor:pointer; color:{{ $viewMode==='list' ? '#fff' : '#333' }}; background:{{ $viewMode==='list' ? '#337ab7' : '#fff' }};">
            <i class="fa fa-list"></i>
        </button>
        <button class="fm-view-btn" data-view="column" onclick="leantime.fm.setView('column')"
                style="padding:5px 12px; border:1px solid {{ $viewMode==='column' ? '#337ab7' : '#ccc' }}; border-radius:6px; font-size:13px; cursor:pointer; color:{{ $viewMode==='column' ? '#fff' : '#333' }}; background:{{ $viewMode==='column' ? '#337ab7' : '#fff' }};">
            <i class="fa fa-columns"></i>
        </button>

        <span style="color:#ccc; margin:0 4px;">|</span>

        <div id="fmBreadcrumbs" style="font-size:13px; color:#666;">
            <a href="javascript:void(0)" onclick="leantime.fm.navigate(null)" style="color:#337ab7;">{{ session('currentProjectName') ?? 'Files' }}</a>
        </div>

        <div id="fmStatus" style="margin-left:auto; font-size:12px; color:#999;"></div>
    </div>

    {{-- Body: full-width file grid (no sidebar) --}}
    <div id="fmMainContent"
         style="min-height:400px;"
         hx-get="{{ BASE_URL }}/files/fileGrid/get?module={{ $module }}&moduleId={{ $moduleId }}&view={{ $viewMode }}{{ $currentFolderId !== null ? '&folderId='.$currentFolderId : '' }}"
         hx-trigger="load"
         hx-swap="innerHTML">
        <div style="padding:40px; text-align:center; color:#999;">
            <i class="fa fa-spinner fa-spin fa-2x"></i><br>
            <span style="margin-top:8px; display:inline-block;">Loading...</span>
        </div>
    </div>
</div>

</div>

{{-- Upload progress --}}
<div id="fmUploadProgress" style="display:none; position:fixed; bottom:16px; right:16px; width:300px; background:#fff; border:1px solid #ddd; border-radius:10px; padding:14px; z-index:9999; box-shadow:0 2px 10px rgba(0,0,0,0.15);">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
        <span style="font-size:13px; font-weight:600;">Uploading...</span>
        <button onclick="jQuery('#fmUploadProgress').hide()" style="background:none;border:none;color:#999;cursor:pointer;">&times;</button>
    </div>
    <div style="background:#eee; height:6px; border-radius:3px;">
        <div id="fmUploadBar" style="background:#337ab7; height:6px; border-radius:3px; width:0%;"></div>
    </div>
    <div id="fmUploadStatus" style="font-size:11px; color:#999; margin-top:4px;"></div>
</div>

{{-- Custom Dialog (replaces prompt/confirm) --}}
<div id="fmDialogOverlay" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.4); z-index:10000; justify-content:center; align-items:center;">
    <div id="fmDialogBox" style="background:#fff; border-radius:12px; box-shadow:0 4px 20px rgba(0,0,0,0.25); width:420px; max-width:90vw;">
        <div style="padding:16px 20px; border-bottom:1px solid #eee; font-size:14px; font-weight:600; color:#333;" id="fmDialogTitle">Dialog</div>
        <div style="padding:16px 20px;">
            <input type="text" id="fmDialogInput" style="width:100%; padding:8px 10px; border:1px solid #ccc; border-radius:6px; font-size:14px; display:none;" placeholder="" />
            <div id="fmDialogMessage" style="font-size:14px; color:#555; display:none;"></div>
        </div>
        <div style="padding:12px 20px; border-top:1px solid #eee; display:flex; justify-content:flex-end; gap:8px;">
            <button id="fmDialogCancel" style="padding:6px 16px; border:1px solid #ccc; background:#fff; border-radius:6px; font-size:13px; cursor:pointer; color:#555;">Cancel</button>
            <button id="fmDialogOk" style="padding:6px 16px; border:none; background:#337ab7; color:#fff; border-radius:6px; font-size:13px; cursor:pointer;">OK</button>
            <button id="fmDialogDelete" style="padding:6px 16px; border:none; background:#d9534f; color:#fff; border-radius:6px; font-size:13px; cursor:pointer; display:none;">Delete</button>
        </div>
    </div>
</div>

{{-- Context Menu --}}
<div id="fmContextMenu" style="display:none; position:fixed; z-index:10001; background:#fff; border:1px solid #ddd; border-radius:10px; box-shadow:0 2px 12px rgba(0,0,0,0.18); min-width:180px; padding:4px 0;">
    <div class="fm-cm-item" data-action="createFile" style="padding:8px 16px; cursor:pointer; font-size:13px; color:#333; display:flex; align-items:center; gap:8px;"
         onmouseover="this.style.background='#f0f7ff'" onmouseout="this.style.background=''">
        <i class="fa fa-file-text-o" style="width:16px; color:#337ab7;"></i> New File
    </div>
    <div class="fm-cm-item" data-action="createFolder" style="padding:8px 16px; cursor:pointer; font-size:13px; color:#333; display:flex; align-items:center; gap:8px;"
         onmouseover="this.style.background='#f0f7ff'" onmouseout="this.style.background=''">
        <i class="fa fa-folder" style="width:16px; color:#f0ad4e;"></i> New Folder
    </div>
    <div class="fm-cm-item" data-action="upload" style="padding:8px 16px; cursor:pointer; font-size:13px; color:#333; display:flex; align-items:center; gap:8px;"
         onmouseover="this.style.background='#f0f7ff'" onmouseout="this.style.background=''">
        <i class="fa fa-upload" style="width:16px; color:#5cb85c;"></i> Upload
    </div>
    <div class="fm-cm-sep" style="border-top:1px solid #eee; margin:4px 0;"></div>
    <div class="fm-cm-item fm-cm-on-item" data-action="rename" style="padding:8px 16px; cursor:pointer; font-size:13px; color:#333; display:none; align-items:center; gap:8px;"
         onmouseover="this.style.background='#f0f7ff'" onmouseout="this.style.background=''">
        <i class="fa fa-pencil" style="width:16px; color:#999;"></i> Rename
    </div>
    <div class="fm-cm-item fm-cm-on-item" data-action="delete" style="padding:8px 16px; cursor:pointer; font-size:13px; color:#c00; display:none; align-items:center; gap:8px;"
         onmouseover="this.style.background='#f0f7ff'" onmouseout="this.style.background=''">
        <i class="fa fa-trash" style="width:16px; color:#c00;"></i> Delete
    </div>
</div>

@once @push('scripts')
<script>
(function() {
    if (typeof leantime.fm === 'undefined') leantime.fm = {};

    var _state = {
        folderId: {{ $currentFolderId ?? 'null' }},
        previewId: null,
        view: '{{ $viewMode }}',
        module: '{{ $module }}',
        moduleId: {{ $moduleId }},
    };

    // ── Dialog system ──────────────────────────────────
    var _dialogCb = null;
    var _dialogMode = '';

    leantime.fm.showDialog = function(mode, opts) {
        opts = opts || {};
        _dialogMode = mode;
        _dialogCb = opts.callback || null;

        var title = '', msg = '', showInput = false, inputVal = '', placeholder = '',
            showOk = true, showDelete = false, okLabel = 'OK', okClass = '';

        switch (mode) {
            case 'createFile':
                title = 'New File'; showInput = true; placeholder = 'File name (e.g. notes.md)'; inputVal = 'untitled.md';
                okLabel = 'Create';
                break;
            case 'createFolder':
                title = 'New Folder'; showInput = true; placeholder = 'Folder name'; inputVal = '';
                okLabel = 'Create';
                break;
            case 'rename':
                title = 'Rename ' + (opts.type || 'item'); showInput = true;
                inputVal = opts.currentName || ''; placeholder = 'New name';
                okLabel = 'Rename';
                break;
            case 'confirmDelete':
                title = 'Delete ' + (opts.type || 'item');
                msg = 'Are you sure you want to delete <strong>' + (opts.name || 'this item') + '</strong>? This cannot be undone.';
                showInput = false;
                showDelete = true; showOk = false;
                break;
        }

        jQuery('#fmDialogTitle').text(title);
        jQuery('#fmDialogInput').toggle(showInput).val(inputVal).attr('placeholder', placeholder);
        jQuery('#fmDialogMessage').toggle(!showInput).html(msg || '');
        jQuery('#fmDialogOk').toggle(showOk).text(okLabel);
        jQuery('#fmDialogDelete').toggle(showDelete);
        jQuery('#fmDialogOverlay').css('display', 'flex');
        setTimeout(function() { jQuery('#fmDialogInput').focus(); }, 100);
    };

    leantime.fm.hideDialog = function() {
        jQuery('#fmDialogOverlay').hide();
        _dialogCb = null; _dialogMode = '';
    };

    jQuery('#fmDialogCancel').on('click', function() { leantime.fm.hideDialog(); });
    jQuery('#fmDialogOverlay').on('click', function(e) {
        if (e.target === this) leantime.fm.hideDialog();
    });

    jQuery('#fmDialogOk').on('click', function() {
        var val = jQuery('#fmDialogInput').val().trim();
        if (_dialogCb) _dialogCb(val);
        leantime.fm.hideDialog();
    });

    jQuery('#fmDialogDelete').on('click', function() {
        if (_dialogCb) _dialogCb();
        leantime.fm.hideDialog();
    });

    // Handle Enter key in dialog input
    jQuery('#fmDialogInput').on('keydown', function(e) {
        if (e.key === 'Enter') jQuery('#fmDialogOk').click();
        if (e.key === 'Escape') leantime.fm.hideDialog();
    });

    // ── Context menu ───────────────────────────────────
    var _ctxTarget = null; // {type:'folder'|'file', id, name}

    jQuery(document).on('contextmenu', '#fmMainContent', function(e) {
        // Only show on rows or empty space within the grid
        var row = jQuery(e.target).closest('[data-fm-type]');
        if (row.length) {
            _ctxTarget = {
                type: row.data('fm-type'),
                id: row.data('fm-id'),
                name: row.data('fm-name')
            };
            jQuery('.fm-cm-on-item').show();
        } else {
            _ctxTarget = null;
            jQuery('.fm-cm-on-item').hide();
        }

        jQuery('#fmContextMenu').css({left: e.pageX + 'px', top: e.pageY + 'px'}).show();
        return false;
    });

    jQuery(document).on('click', function() { jQuery('#fmContextMenu').hide(); });

    jQuery('#fmContextMenu .fm-cm-item').on('click', function() {
        var action = jQuery(this).data('action');
        jQuery('#fmContextMenu').hide();

        switch (action) {
            case 'createFile': leantime.fm.createFile(); break;
            case 'createFolder': leantime.fm.createFolder(); break;
            case 'upload': jQuery('#fmUploadInput').click(); break;
            case 'rename':
                if (_ctxTarget) {
                    leantime.fm.rename(_ctxTarget.id, _ctxTarget.type, _ctxTarget.name);
                }
                break;
            case 'delete':
                if (_ctxTarget) {
                    leantime.fm.del(_ctxTarget.id, _ctxTarget.type, _ctxTarget.name);
                }
                break;
        }
    });

    // ── API helper ─────────────────────────────────────
    function _api(action, data) {
        var fd = new FormData();
        fd.append('action', action);
        for (var k in data) {
            if (data.hasOwnProperty(k) && data[k] !== null && data[k] !== undefined) {
                fd.append(k, data[k]);
            }
        }
        return fetch('{{ BASE_URL }}/api/fileManager', {
            method: 'POST', body: fd,
            headers: { 'X-CSRF-TOKEN': jQuery('meta[name=csrf-token]').attr('content') }
        }).then(function(r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        }).catch(function(err) {
            console.error('FileManager API error:', err);
            return {success: false, error: 'Network or server error'};
        });
    }

    function _refresh() {
        var url = '{{ BASE_URL }}/files/fileGrid/get?module=' + _state.module
            + '&moduleId=' + _state.moduleId + '&view=' + _state.view
            + (_state.folderId !== null ? '&folderId=' + _state.folderId : '')
            + (_state.previewId !== null ? '&previewId=' + _state.previewId : '');
        htmx.ajax('GET', url, {target: '#fmMainContent', swap: 'innerHTML'});
    }

    function _refreshBreadcrumbs() {
        if (_state.folderId === null) {
            jQuery('#fmBreadcrumbs').html(
                '<a href="javascript:void(0)" onclick="leantime.fm.navigate(null)" style="color:#337ab7;">' +
                '{{ \session('currentProjectName') ?? 'Files' }}' + '</a>'
            );
            return;
        }
        _api('list', {module: _state.module, moduleId: _state.moduleId, folderId: _state.folderId})
            .then(function(r) {
                if (!r || !r.breadcrumbs) return;
                var html = '<a href="javascript:void(0)" onclick="leantime.fm.navigate(null)" style="color:#337ab7;">' +
                    '{{ \session('currentProjectName') ?? 'Files' }}' + '</a>';
                for (var i = 0; i < r.breadcrumbs.length; i++) {
                    html += ' / <a href="javascript:void(0)" onclick="leantime.fm.navigate(' + r.breadcrumbs[i].id + ')" style="color:#337ab7;">' +
                        _esc(r.breadcrumbs[i].name) + '</a>';
                }
                jQuery('#fmBreadcrumbs').html(html);
            });
    }

    function _esc(s) {
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(s));
        return d.innerHTML;
    }

    // ── Public API ─────────────────────────────────────
    leantime.fm.navigate = function(folderId) {
        _state.folderId = folderId;
        _refresh();
        _refreshBreadcrumbs();
    };

    leantime.fm.setView = function(view) {
        _state.view = view;
        // Update button active styles
        jQuery('.fm-view-btn').each(function() {
            var btn = jQuery(this);
            var isActive = btn.data('view') === view;
            btn.css({
                borderColor: isActive ? '#337ab7' : '#ccc',
                color: isActive ? '#fff' : '#333',
                background: isActive ? '#337ab7' : '#fff'
            });
        });
        _refresh();
    };

    // Column navigation: clicked folder becomes current (always in col 2)
    leantime.fm.navigateCol = function(folderId) {
        _state.folderId = folderId;
        _state.previewId = null;
        _state.view = 'column';
        jQuery('.fm-view-btn').each(function() {
            var btn = jQuery(this);
            var isActive = btn.data('view') === 'column';
            btn.css({
                borderColor: isActive ? '#337ab7' : '#ccc',
                color: isActive ? '#fff' : '#333',
                background: isActive ? '#337ab7' : '#fff'
            });
        });
        _refresh();
        _refreshBreadcrumbs();
    };

    // Preview a folder from col 2 in col 3
    leantime.fm.previewCol = function(folderId) {
        _state.previewId = (_state.previewId === folderId) ? null : folderId;
        _state.view = 'column';
        _refresh();
    };

    leantime.fm.createFolder = function() {
        leantime.fm.showDialog('createFolder', {
            callback: function(name) {
                if (!name) return;
                _api('createFolder', {
                    name: name,
                    module: _state.module,
                    moduleId: _state.moduleId,
                    folderId: _state.folderId
                }).then(function(r) {
                    if (r.success) _refresh();
                    else alert(r.error || 'Failed to create folder');
                });
            }
        });
    };

    leantime.fm.createFile = function() {
        leantime.fm.showDialog('createFile', {
            callback: function(name) {
                if (!name) return;
                _api('createFile', {
                    name: name,
                    folderId: _state.folderId,
                    module: _state.module,
                    moduleId: _state.moduleId
                }).then(function(r) {
                    if (r.success) _refresh();
                    else alert(r.error || 'Failed to create file');
                });
            }
        });
    };

    leantime.fm.rename = function(id, type, currentName) {
        leantime.fm.showDialog('rename', {
            type: type, currentName: currentName,
            callback: function(newName) {
                if (!newName || newName === currentName) return;
                _api('rename', {id: id, type: type, name: newName}).then(function(r) {
                    if (r.success) _refresh();
                    else alert('Rename failed');
                });
            }
        });
    };

    leantime.fm.del = function(id, type, name) {
        name = name || type + ' #' + id;
        leantime.fm.showDialog('confirmDelete', {
            type: type, name: name,
            callback: function() {
                _api('delete', {id: id, type: type}).then(function(r) {
                    if (r.success) _refresh();
                    else alert('Delete failed');
                });
            }
        });
    };

    leantime.fm.moveFile = function(fileId, folderId) {
        _api('move', {fileId: fileId, folderId: folderId}).then(function(r) {
            if (r.success) _refresh();
        });
    };

    leantime.fm.openEditor = function(fileId) {
        window.location.hash = '#/files/textEditor/' + fileId;
    };

    leantime.fm.uploadFile = function(input) {
        var file = input.files[0]; if (!file) return;
        var fd = new FormData(); fd.append('file', file);
        var url = '{{ BASE_URL }}/api/files?module=' + _state.module + '&moduleId=' + _state.moduleId
            + (_state.folderId !== null ? '&folderId=' + _state.folderId : '');
        jQuery('#fmUploadProgress').show(); jQuery('#fmUploadBar').css('width', '0%');
        jQuery('#fmUploadStatus').text('Uploading...');
        jQuery.ajax({
            url: url, method: 'POST', data: fd, processData: false, contentType: false,
            xhr: function() {
                var xhr = new XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) jQuery('#fmUploadBar').css('width', Math.round(e.loaded/e.total*100) + '%');
                });
                return xhr;
            },
            success: function() {
                jQuery('#fmUploadBar').css('width', '100%');
                jQuery('#fmUploadStatus').text('Done!');
                setTimeout(function() { jQuery('#fmUploadProgress').hide(); }, 1500);
                _refresh();
            },
            error: function() {
                jQuery('#fmUploadStatus').text('Upload failed');
                setTimeout(function() { jQuery('#fmUploadProgress').hide(); }, 3000);
            }
        });
        input.value = '';
    };

    document.body.addEventListener('lt:files:folder.changed', function(evt) {
        if (evt.detail && evt.detail.folderId !== undefined) _state.folderId = evt.detail.folderId;
    });

    // Initial breadcrumb refresh
    _refreshBreadcrumbs();
})();
</script>
@endpush @endonce

@endsection
