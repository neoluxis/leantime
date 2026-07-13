@php
    $currentFolderId = $currentFolderId ?? null;
    $folders = $folders ?? [];
    $module = $module ?? 'project';
    $moduleId = $moduleId ?? 0;
@endphp

<div style="padding:12px; background:#fafafa; min-height:100%;">
    <div style="font-size:10px; text-transform:uppercase; color:#999; font-weight:600; letter-spacing:0.5px; margin-bottom:8px; padding:0 6px;">
        Folders
    </div>

    <ul style="list-style:none; padding:0; margin:0;">
        @foreach ($folders as $folder)
            <li style="margin-bottom:1px;" data-folder-id="{{ $folder['id'] }}">
                <a href="javascript:void(0)"
                   onclick="leantime.fm.navigate({{ $folder['id'] }})"
                   style="display:flex; align-items:center; padding:6px 8px; border-radius:3px; font-size:13px; text-decoration:none; transition:background 0.1s;
                          {{ $currentFolderId == $folder['id'] ? 'background:#d9edf7; color:#31708f; font-weight:500;' : 'color:#555;' }}"
                   onmouseover="if({{ $currentFolderId == $folder['id'] ? 'false' : 'true' }}) this.style.background='#f0f0f0'"
                   onmouseout="if({{ $currentFolderId == $folder['id'] ? 'false' : 'true' }}) this.style.background=''">
                    <i class="fa fa-folder" style="margin-right:8px; width:16px; {{ $currentFolderId == $folder['id'] ? 'color:#31708f;' : 'color:#f0ad4e;' }}"></i>
                    <span style="flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ htmlspecialchars($folder['name']) }}</span>
                </a>
            </li>
        @endforeach
    </ul>

    @if (empty($folders))
        <div style="text-align:center; padding:20px; color:#bbb; font-size:12px;">No subfolders</div>
    @endif
</div>
