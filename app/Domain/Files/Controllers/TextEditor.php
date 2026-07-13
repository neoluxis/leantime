<?php

namespace Leantime\Domain\Files\Controllers;

use Leantime\Core\Controller\Controller;
use Leantime\Domain\Files\Services\Files as FileService;
use Symfony\Component\HttpFoundation\Response;

class TextEditor extends Controller
{
    private FileService $filesService;

    public function init(FileService $filesService): void
    {
        $this->filesService = $filesService;
    }

    /**
     * Render the TipTap text editor modal for a given file.
     */
    public function run(): Response
    {
        $fileId = (int) ($_GET['id'] ?? 0);

        $content = $this->filesService->readFileContent($fileId);

        // Get file metadata via repository
        $fileRepo = app()->make(\Leantime\Domain\Files\Repositories\Files::class);
        $file = $fileRepo->getFile($fileId);

        if (! $file) {
            $this->tpl->setNotification('File not found', 'error');

            return $this->tpl->displayPartial('files.textEditorModal');
        }

        $fileName = $file['realName'].'.'.$file['extension'];

        $this->tpl->assign('fileId', $fileId);
        $this->tpl->assign('fileName', $fileName);
        $this->tpl->assign('fileContent', $content !== false ? $content : '');
        $this->tpl->assign('fileExtension', $file['extension']);

        return $this->tpl->displayPartial('files.textEditorModal');
    }
}
