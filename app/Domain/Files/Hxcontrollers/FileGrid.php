<?php

namespace Leantime\Domain\Files\Hxcontrollers;

use Leantime\Core\Controller\HtmxController;
use Leantime\Core\Files\FileManager;
use Leantime\Domain\Files\Services\Files;

class FileGrid extends HtmxController
{
    protected static string $view = 'files::partials.fileGrid';

    private Files $filesService;

    public function init(Files $filesService): void
    {
        $this->filesService = $filesService;
    }

    /**
     * GET — render the file grid for the selected folder.
     */
    public function get()
    {
        $module = $_GET['module'] ?? 'project';
        $moduleId = (int) ($_GET['moduleId'] ?? session('currentProject', 0));
        $folderId = isset($_GET['folderId']) && $_GET['folderId'] !== '' ? (int) $_GET['folderId'] : null;

        $files = $this->filesService->getFilesByFolder($module, $moduleId, $folderId);
        $folders = $this->filesService->getFolders($module, $moduleId, $folderId);

        $this->tpl->assign('files', $files);
        $this->tpl->assign('folders', $folders);
        $this->tpl->assign('currentFolderId', $folderId);
        $this->tpl->assign('module', $module);
        $this->tpl->assign('moduleId', $moduleId);
        $this->tpl->assign('imgExtensions', ['jpg', 'jpeg', 'png', 'gif', 'psd', 'bmp', 'tif', 'thm', 'yuv', 'webpe']);
        $this->tpl->assign('maxSize', FileManager::getMaximumFileUploadSize());

        // Column view: parent context + preview data
        $view = $_GET['view'] ?? 'list';
        if ($view === 'column') {
            $repo = app()->make(\Leantime\Domain\Files\Repositories\Files::class);

            // Parent level (col 1) — siblings of current folder
            $parentId = null;
            $parentFolderName = '';
            $parentFolders = [];
            $parentFiles = [];
            $currentFolderName = '';

            if ($folderId !== null) {
                $currentFolder = $repo->getFileFolderById($folderId);
                if ($currentFolder) {
                    $parentId = isset($currentFolder['parentId']) ? (int) $currentFolder['parentId'] : null;
                    $currentFolderName = $currentFolder['name'] ?? '';
                }
                $parentFolders = $this->filesService->getFolders($module, $moduleId, $parentId);
                $parentFiles = $this->filesService->getFilesByFolder($module, $moduleId, $parentId);
            }

            // Parent folder name for the back-link header
            if ($parentId !== null) {
                $parentFolder = $repo->getFileFolderById($parentId);
                $parentFolderName = $parentFolder['name'] ?? '';
            }

            // Preview column (col 3) — only when explicitly requested (no auto-expand)
            $previewId = isset($_GET['previewId']) && $_GET['previewId'] !== '' ? (int) $_GET['previewId'] : null;
            $previewFolders = [];
            $previewFiles = [];
            $previewFolderName = '';
            if ($previewId !== null) {
                $pf = $repo->getFileFolderById($previewId);
                $previewFolderName = $pf['name'] ?? '';
                $previewFolders = $this->filesService->getFolders($module, $moduleId, $previewId);
                $previewFiles = $this->filesService->getFilesByFolder($module, $moduleId, $previewId);
            }

            $this->tpl->assign('parentId', $parentId);
            $this->tpl->assign('parentFolderName', $parentFolderName);
            $this->tpl->assign('parentFolders', $parentFolders);
            $this->tpl->assign('parentFiles', $parentFiles);
            $this->tpl->assign('currentFolderName', $currentFolderName);
            $this->tpl->assign('previewId', $previewId);
            $this->tpl->assign('previewFolders', $previewFolders);
            $this->tpl->assign('previewFiles', $previewFiles);
            $this->tpl->assign('previewFolderName', $previewFolderName);
        }
    }

    /**
     * POST — move a file to a different folder.
     */
    public function moveFile()
    {
        $fileId = (int) ($_POST['fileId'] ?? 0);
        $folderId = isset($_POST['folderId']) && $_POST['folderId'] !== '' ? (int) $_POST['folderId'] : null;

        $this->filesService->moveFileToFolder($fileId, $folderId);

        $this->get();

        $this->setHTMXEvent('{"lt:files:file.moved": {}}');
    }
}
