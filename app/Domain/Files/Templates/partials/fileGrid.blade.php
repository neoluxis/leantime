@php
    $files = $files ?? [];
    $folders = $folders ?? [];
    $currentFolderId = $currentFolderId ?? null;
    $module = $module ?? 'project';
    $moduleId = $moduleId ?? 0;
    $view = $_GET['view'] ?? 'list';
    $editableExts = ['md', 'txt', 'html', 'css', 'js', 'php', 'json', 'xml', 'yml', 'yaml', 'ini', 'cfg', 'log', 'csv'];
@endphp

<div style="padding:16px; background:#fff;">

@if (empty($files) && empty($folders) && $currentFolderId !== null)
    <div style="text-align:center; padding:60px 20px; color:#999;">
        <i class="fa fa-folder-open" style="font-size:48px; display:block; margin-bottom:12px;"></i>
        <p style="font-size:15px; color:#666;">This folder is empty</p>
        <p style="font-size:13px;">Right-click to create a new file or folder.</p>
    </div>
@else

@if ($view === 'column')
    {{-- Column View: col1=parent, col2=current(ALWAYS), col3=preview --}}
    @php
        $parentId = $parentId ?? null;
        $parentFolderName = $parentFolderName ?? '';
        $parentFolders = $parentFolders ?? [];
        $parentFiles = $parentFiles ?? [];
        $currentFolderName = $currentFolderName ?? '';
        $previewId = $previewId ?? null;
        $previewFolderName = $previewFolderName ?? '';
        $previewFolders = $previewFolders ?? [];
        $previewFiles = $previewFiles ?? [];
    @endphp
    <div id="fmColumnView" style="display:flex; overflow-x:auto; min-height:400px;">

        {{-- Column 1: Parent level — siblings of current folder --}}
        <div class="fm-col" style="width:240px; min-width:190px; flex-shrink:0; border-right:1px solid #eee; overflow-y:auto; min-height:400px;">
            @if ($currentFolderId === null)
                {{-- At root: col 1 is empty --}}
                <div style="padding:8px 12px; background:#f9f9f9; border-bottom:1px solid #eee; font-size:11px; color:#bbb; text-transform:uppercase; letter-spacing:0.5px;">
                    &nbsp;
                </div>
                <div style="text-align:center; padding:60px 20px; color:#ddd;">
                    <i class="fa fa-chevron-left" style="font-size:20px; display:block; margin-bottom:4px;"></i>
                </div>
            @else
                <div style="padding:8px 12px; background:#f5f5f5; border-bottom:1px solid #ddd; font-size:12px; font-weight:600; color:#555;">
                    @if ($parentId === null)
                        <i class="fa fa-folder" style="margin-right:6px; color:#f0ad4e;"></i>{{ session('currentProjectName') ?? 'Files' }}
                    @else
                        <a href="javascript:void(0)" onclick="leantime.fm.navigateCol({{ $parentId }})"
                           style="color:#337ab7; text-decoration:none;">
                            <i class="fa fa-arrow-left" style="margin-right:4px; font-size:10px;"></i>{{ htmlspecialchars($parentFolderName) }}
                        </a>
                    @endif
                </div>
                @foreach ($parentFolders as $f)
                    <div data-fm-type="folder" data-fm-id="{{ $f['id'] }}" data-fm-name="{{ htmlspecialchars($f['name']) }}"
                         style="display:flex; align-items:center; padding:7px 12px; cursor:pointer; border-bottom:1px solid #f0f0f0; transition:background 0.1s;
                                {{ $f['id'] == $currentFolderId ? 'background:#d9edf7; color:#31708f; font-weight:500;' : '' }}"
                         onclick="leantime.fm.navigateCol({{ $f['id'] }})"
                         onmouseover="if({{ $f['id'] == $currentFolderId ? 'false' : 'true' }}) this.style.background='#fafafa'"
                         onmouseout="if({{ $f['id'] == $currentFolderId ? 'false' : 'true' }}) this.style.background=''">
                        <i class="fa fa-folder" style="color:#f0ad4e; margin-right:8px; width:16px; flex-shrink:0;"></i>
                        <span style="font-size:13px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ htmlspecialchars($f['name']) }}</span>
                        @if ($f['id'] == $currentFolderId)
                            <i class="fa fa-chevron-right" style="color:#31708f; font-size:10px; margin-left:auto; flex-shrink:0;"></i>
                        @endif
                    </div>
                @endforeach
                @if (count($parentFiles) > 0)
                    <div style="border-top:1px solid #eee; margin:4px 0;"></div>
                @endif
                @foreach ($parentFiles as $file)
                    <div data-fm-type="file" data-fm-id="{{ $file['id'] }}" data-fm-name="{{ htmlspecialchars($file['realName']) }}.{{ $file['extension'] }}"
                         style="display:flex; align-items:center; padding:7px 12px; cursor:pointer; border-bottom:1px solid #fafafa; color:#888; font-size:12px;"
                         @if (in_array(strtolower($file['extension']), $editableExts))
                             ondblclick="leantime.fm.openEditor({{ $file['id'] }})"
                         @else
                             ondblclick="window.open('{{ BASE_URL }}/files/get?module={{ $file['module'] }}&encName={{ $file['encName'] }}&ext={{ $file['extension'] }}&realName={{ $file['realName'] }}', '_blank')"
                         @endif
                         onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background=''">
                        <i class="fa fa-file-o" style="color:#bbb; margin-right:8px; width:16px; flex-shrink:0;"></i>
                        <span style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ htmlspecialchars($file['realName']) }}.{{ $file['extension'] }}</span>
                    </div>
                @endforeach
            @endif
        </div>

        {{-- Column 2: CURRENT FOLDER — ALWAYS --}}
        <div class="fm-col fm-col-current" style="width:260px; min-width:210px; flex-shrink:0; border-right:1px solid #eee; overflow-y:auto; min-height:400px; background:#fdfdfd;">
            <div style="padding:8px 12px; background:#f0f7ff; border-bottom:1px solid #bce8f1; font-size:12px; font-weight:600; color:#31708f;">
                @if ($currentFolderId === null)
                    <i class="fa fa-folder-open" style="margin-right:6px;"></i>{{ session('currentProjectName') ?? 'Files' }}
                @else
                    <i class="fa fa-folder-open" style="margin-right:6px;"></i>{{ htmlspecialchars($currentFolderName) }}
                @endif
            </div>

            @foreach ($folders as $folder)
                <div data-fm-type="folder" data-fm-id="{{ $folder['id'] }}" data-fm-name="{{ htmlspecialchars($folder['name']) }}"
                     style="display:flex; align-items:center; padding:7px 12px; cursor:pointer; border-bottom:1px solid #f0f0f0; transition:background 0.1s;
                            {{ $previewId == $folder['id'] ? 'background:#e8f4e8;' : '' }}"
                     onclick="leantime.fm.navigateCol({{ $folder['id'] }})"
                     onmouseover="if({{ $previewId == $folder['id'] ? 'false' : 'true' }}) this.style.background='#f0f7ff'"
                     onmouseout="if({{ $previewId == $folder['id'] ? 'false' : 'true' }}) this.style.background=''">
                    <i class="fa fa-folder" style="color:#f0ad4e; margin-right:8px; width:16px; flex-shrink:0;"></i>
                    <span style="font-size:13px; flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ htmlspecialchars($folder['name']) }}</span>
                    <i class="fa fa-chevron-right" style="color:#ccc; font-size:10px; margin-left:4px; flex-shrink:0;"></i>
                </div>
            @endforeach

            @if (count($files) > 0)
                <div style="border-top:1px solid #eee; margin:4px 0;"></div>
            @endif
            @foreach ($files as $file)
                <div data-fm-type="file" data-fm-id="{{ $file['id'] }}" data-fm-name="{{ htmlspecialchars($file['realName']) }}.{{ $file['extension'] }}"
                     style="display:flex; align-items:center; padding:7px 12px; cursor:pointer; border-bottom:1px solid #fafafa; transition:background 0.1s;"
                     @if (in_array(strtolower($file['extension']), $editableExts))
                         ondblclick="leantime.fm.openEditor({{ $file['id'] }})"
                     @else
                         ondblclick="window.open('{{ BASE_URL }}/files/get?module={{ $file['module'] }}&encName={{ $file['encName'] }}&ext={{ $file['extension'] }}&realName={{ $file['realName'] }}', '_blank')"
                     @endif
                     onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background=''">
                    <i class="fa {{ in_array(strtolower($file['extension']), ['jpg','jpeg','png','gif','svg','webp','bmp']) ? 'fa-file-image-o' : 'fa-file-o' }}" style="color:#999; margin-right:8px; width:16px; flex-shrink:0;"></i>
                    <span style="font-size:13px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ htmlspecialchars($file['realName']) }}.{{ $file['extension'] }}</span>
                </div>
            @endforeach
        </div>

        {{-- Column 3: Preview — only when a folder in col 2 is clicked --}}
        <div class="fm-col" style="width:260px; min-width:210px; flex-shrink:0; overflow-y:auto; min-height:400px;">
            @if ($previewId !== null)
                <div style="padding:8px 12px; background:#f9f9f9; border-bottom:1px solid #eee; font-size:12px; font-weight:600; color:#555;">
                    <i class="fa fa-folder-open" style="margin-right:6px; color:#f0ad4e;"></i>{{ htmlspecialchars($previewFolderName) }}
                </div>
                @foreach ($previewFolders as $f)
                    <div data-fm-type="folder" data-fm-id="{{ $f['id'] }}" data-fm-name="{{ htmlspecialchars($f['name']) }}"
                         style="display:flex; align-items:center; padding:7px 12px; cursor:pointer; border-bottom:1px solid #f0f0f0; transition:background 0.1s;"
                         onclick="leantime.fm.navigateCol({{ $f['id'] }})"
                         onmouseover="this.style.background='#f0f7ff'" onmouseout="this.style.background=''">
                        <i class="fa fa-folder" style="color:#f0ad4e; margin-right:8px; width:16px; flex-shrink:0;"></i>
                        <span style="font-size:13px; flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ htmlspecialchars($f['name']) }}</span>
                        <i class="fa fa-chevron-right" style="color:#ccc; font-size:10px; margin-left:4px; flex-shrink:0;"></i>
                    </div>
                @endforeach
                @if (count($previewFiles) > 0)
                    <div style="border-top:1px solid #eee; margin:4px 0;"></div>
                @endif
                @foreach ($previewFiles as $file)
                    <div data-fm-type="file" data-fm-id="{{ $file['id'] }}" data-fm-name="{{ htmlspecialchars($file['realName']) }}.{{ $file['extension'] }}"
                         style="display:flex; align-items:center; padding:7px 12px; cursor:pointer; border-bottom:1px solid #fafafa; font-size:13px;"
                         @if (in_array(strtolower($file['extension']), $editableExts))
                             ondblclick="leantime.fm.openEditor({{ $file['id'] }})"
                         @else
                             ondblclick="window.open('{{ BASE_URL }}/files/get?module={{ $file['module'] }}&encName={{ $file['encName'] }}&ext={{ $file['extension'] }}&realName={{ $file['realName'] }}', '_blank')"
                         @endif
                         onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background=''">
                        <i class="fa fa-file-o" style="color:#999; margin-right:8px; width:16px; flex-shrink:0;"></i>
                        <span style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ htmlspecialchars($file['realName']) }}.{{ $file['extension'] }}</span>
                    </div>
                @endforeach
                @if (empty($previewFolders) && empty($previewFiles))
                    <div style="text-align:center; padding:50px 20px; color:#bbb;">
                        <span style="font-size:12px;">Empty folder</span>
                    </div>
                @endif
            @else
                <div style="padding:8px 12px; background:#f9f9f9; border-bottom:1px solid #eee; font-size:11px; text-transform:uppercase; color:#888; font-weight:600; letter-spacing:0.5px;">
                    Preview
                </div>
                <div style="text-align:center; padding:60px 20px; color:#bbb;">
                    <i class="fa fa-folder-open" style="font-size:36px; display:block; margin-bottom:8px;"></i>
                    <p style="font-size:13px;">Click a folder to preview</p>
                </div>
            @endif
        </div>

    </div>

@else
    {{-- List / Table View --}}
    <table style="width:100%; font-size:13px; border-collapse:collapse;">
        <thead>
            <tr style="border-bottom:2px solid #eee; text-align:left; font-size:11px; text-transform:uppercase; color:#888; letter-spacing:0.5px;">
                <th style="padding:8px 12px; width:32px;"></th>
                <th style="padding:8px 12px;">Name</th>
                <th style="padding:8px 12px; width:80px;">Size</th>
                <th style="padding:8px 12px; width:130px;">Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($folders as $folder)
                <tr data-fm-type="folder" data-fm-id="{{ $folder['id'] }}" data-fm-name="{{ htmlspecialchars($folder['name']) }}"
                    style="border-bottom:1px solid #f5f5f5; cursor:pointer; transition:background 0.15s;"
                    ondblclick="leantime.fm.navigate({{ $folder['id'] }})"
                    onmouseover="this.style.background='#f0f7ff'" onmouseout="this.style.background=''">
                    <td style="padding:8px 12px;"><i class="fa fa-folder" style="color:#f0ad4e;"></i></td>
                    <td style="padding:8px 12px; font-weight:500;">{{ htmlspecialchars($folder['name']) }}</td>
                    <td style="padding:8px 12px; color:#bbb;">--</td>
                    <td style="padding:8px 12px; color:#999;">{{ isset($folder['date']) ? substr($folder['date'], 0, 10) : '--' }}</td>
                </tr>
            @endforeach

            @foreach ($files as $file)
                <tr data-fm-type="file" data-fm-id="{{ $file['id'] }}" data-fm-name="{{ htmlspecialchars($file['realName']) }}.{{ $file['extension'] }}"
                    style="border-bottom:1px solid #fafafa; cursor:pointer; transition:background 0.15s;"
                    @if (in_array(strtolower($file['extension']), $editableExts))
                        ondblclick="leantime.fm.openEditor({{ $file['id'] }})"
                    @else
                        ondblclick="window.open('{{ BASE_URL }}/files/get?module={{ $file['module'] }}&encName={{ $file['encName'] }}&ext={{ $file['extension'] }}&realName={{ $file['realName'] }}', '_blank')"
                    @endif
                    onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background=''">
                    <td style="padding:8px 12px;">
                        @if (in_array(strtolower($file['extension']), ['jpg','jpeg','png','gif','svg','webp','bmp']))
                            <i class="fa fa-file-image-o" style="color:#5cb85c;"></i>
                        @elseif (in_array(strtolower($file['extension']), ['pdf']))
                            <i class="fa fa-file-pdf-o" style="color:#d9534f;"></i>
                        @elseif (in_array(strtolower($file['extension']), ['zip','rar','7z','tar','gz']))
                            <i class="fa fa-file-archive-o" style="color:#f0ad4e;"></i>
                        @elseif (in_array(strtolower($file['extension']), $editableExts))
                            <i class="fa fa-file-text-o" style="color:#337ab7;"></i>
                        @else
                            <i class="fa fa-file-o" style="color:#999;"></i>
                        @endif
                    </td>
                    <td style="padding:8px 12px;">
                        <span style="font-weight:500;">{{ htmlspecialchars(substr($file['realName'], 0, 40)) }}</span>
                        <span style="color:#aaa;">.{{ $file['extension'] }}</span>
                    </td>
                    <td style="padding:8px 12px; color:#bbb;">--</td>
                    <td style="padding:8px 12px; color:#999;">{{ substr($file['date'] ?? '', 0, 10) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

@endif

<div style="font-size:11px; color:#999; margin-top:12px; padding:0 12px;">
    {{ count($files) }} file(s)@if (count($folders) > 0), {{ count($folders) }} folder(s)@endif
</div>
</div>
