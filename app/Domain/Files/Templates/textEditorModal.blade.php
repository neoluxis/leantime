@php
    $fileId = $fileId ?? 0;
    $fileName = $fileName ?? 'untitled.md';
    $fileContent = $fileContent ?? '';
    $fileExtension = $fileExtension ?? 'txt';
    $isMarkdown = in_array(strtolower($fileExtension), ['md', 'markdown']);
@endphp

<div style="display:flex; flex-direction:column; min-height:500px;">

    {{-- Header --}}
    <div style="display:flex; align-items:center; justify-content:space-between; padding:10px 16px; background:#f5f5f5; border-bottom:1px solid #ddd;">
        <div style="display:flex; align-items:center; gap:10px;">
            <i class="fa fa-file-text-o" style="color:#337ab7; font-size:16px;"></i>
            <span style="font-weight:500; font-size:14px;">{{ htmlspecialchars($fileName) }}</span>
            <span id="editorSaveStatus" style="font-size:12px; color:#999;"></span>
        </div>
        <div style="display:flex; gap:8px;">
            @if ($isMarkdown)
            <button id="btnTogglePreview" onclick="leantime.fm.togglePreview()"
                    style="padding:6px 12px; background:#fff; border:1px solid #ccc; border-radius:6px; font-size:12px; cursor:pointer; color:#555;">
                <i class="fa fa-eye"></i> Preview
            </button>
            @endif
            <button onclick="leantime.fm.editorSave()"
                    style="padding:6px 16px; background:#337ab7; color:#fff; border:none; border-radius:6px; font-size:13px; cursor:pointer;">
                <i class="fa fa-save"></i> Save
            </button>
            <button onclick="jQuery.nmTop().close()"
                    style="padding:6px 16px; background:#fff; border:1px solid #ccc; border-radius:6px; font-size:13px; cursor:pointer; color:#555;">
                Close
            </button>
        </div>
    </div>

    {{-- Editor body --}}
    <div id="editorBody" style="flex:1; display:flex; overflow:hidden;">
        {{-- Editing pane --}}
        <div id="editorEditPane" style="flex:1; display:flex; flex-direction:column; min-width:0;">
            <textarea id="fileEditorTextarea"
                      style="flex:1; width:100%; padding:16px; border:none; resize:none; font-family:'Menlo','Monaco','Consolas',monospace; font-size:13px; line-height:1.6; background:#fafafa; color:#333; outline:none; tab-size:4;"
                      placeholder="{{ $isMarkdown ? '# Start writing markdown...' : 'Start writing...' }}">{{ htmlspecialchars($fileContent) }}</textarea>
        </div>

        {{-- Preview pane (markdown only) --}}
        @if ($isMarkdown)
        <div id="editorPreviewPane" style="flex:1; overflow:auto; padding:16px 24px; background:#fff; border-left:1px solid #e0e0e0; display:none;">
            <div id="editorPreviewContent" style="font-size:14px; line-height:1.7; color:#333;"></div>
        </div>
        @endif
    </div>

</div>

<script>
(function() {
    if (!leantime.fm) leantime.fm = {};

    var _fileId = {{ $fileId }};
    var _saveTimer = null;
    var _textarea = document.getElementById('fileEditorTextarea');
    var _isMarkdown = {{ $isMarkdown ? 'true' : 'false' }};
    var _previewVisible = false;

    // ── Save ─────────────────────────────────────────
    leantime.fm.editorSave = function() {
        var content = _textarea ? _textarea.value : '';

        jQuery('#editorSaveStatus').text('Saving...').css('color', '#999');

        var fd = new FormData();
        fd.append('action', 'write');
        fd.append('fileId', _fileId);
        fd.append('content', content);

        fetch('{{ BASE_URL }}/api/fileManager', {
            method: 'POST',
            body: fd,
            headers: { 'X-CSRF-TOKEN': jQuery('meta[name=csrf-token]').attr('content') }
        }).then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                jQuery('#editorSaveStatus').text('Saved').css('color', '#5cb85c');
                setTimeout(function() {
                    jQuery('#editorSaveStatus').text('').css('color', '#999');
                }, 2000);
            } else {
                jQuery('#editorSaveStatus').text('Error saving').css('color', '#d9534f');
            }
        }).catch(function() {
            jQuery('#editorSaveStatus').text('Save failed').css('color', '#d9534f');
        });
    };

    // ── Markdown preview ─────────────────────────────
    leantime.fm.togglePreview = function() {
        _previewVisible = !_previewVisible;
        var previewPane = document.getElementById('editorPreviewPane');
        var editPane = document.getElementById('editorEditPane');
        var btn = document.getElementById('btnTogglePreview');

        if (_previewVisible) {
            editPane.style.flex = '1';
            previewPane.style.display = 'block';
            previewPane.style.flex = '1';
            btn.innerHTML = '<i class="fa fa-pencil"></i> Edit';
            leantime.fm.updatePreview();
        } else {
            previewPane.style.display = 'none';
            editPane.style.flex = '1';
            btn.innerHTML = '<i class="fa fa-eye"></i> Preview';
        }
    };

    leantime.fm.updatePreview = function() {
        if (!_isMarkdown || !_previewVisible) return;
        var content = _textarea ? _textarea.value : '';
        var previewDiv = document.getElementById('editorPreviewContent');
        if (!previewDiv) return;

        try {
            if (typeof marked !== 'undefined') {
                previewDiv.innerHTML = marked.parse(content);
            } else if (typeof window.marked !== 'undefined') {
                previewDiv.innerHTML = window.marked.parse(content);
            } else {
                // Fallback: basic HTML escape + line breaks
                previewDiv.innerHTML = '<pre style="white-space:pre-wrap;font-family:inherit;">' +
                    content.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;') + '</pre>';
            }
        } catch(e) {
            previewDiv.innerHTML = '<p style="color:#c00;">Preview error</p>';
        }
    };

    // ── Textarea events ──────────────────────────────
    if (_textarea) {
        _textarea.addEventListener('input', function() {
            jQuery('#editorSaveStatus').text('Unsaved').css('color', '#f0ad4e');
            clearTimeout(_saveTimer);
            _saveTimer = setTimeout(function() {
                leantime.fm.editorSave();
            }, 2000);
            // Update preview live
            if (_previewVisible) {
                clearTimeout(_saveTimer);
                leantime.fm.updatePreview();
                _saveTimer = setTimeout(function() { leantime.fm.editorSave(); }, 2000);
            }
        });

        _textarea.addEventListener('keydown', function(e) {
            if (e.key === 'Tab') {
                e.preventDefault();
                var start = this.selectionStart;
                var end = this.selectionEnd;
                this.value = this.value.substring(0, start) + '\t' + this.value.substring(end);
                this.selectionStart = this.selectionEnd = start + 1;
            }
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                leantime.fm.editorSave();
            }
        });

        _textarea.focus();
    }

    // Initial preview render
    if (_isMarkdown) {
        leantime.fm.updatePreview();
    }
})();
</script>
