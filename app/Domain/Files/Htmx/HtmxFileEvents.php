<?php

namespace Leantime\Domain\Files\Htmx;

enum HtmxFileEvents: string
{
    /** Fired when a folder is selected, created, renamed, or deleted. */
    case FOLDER_CHANGED = 'lt:files:folder.changed';

    /** Fired when a file is moved to a different folder. */
    case FILE_MOVED = 'lt:files:file.moved';

    /** Fired when a file upload completes. */
    case FILE_UPLOADED = 'lt:files:file.uploaded';
}
