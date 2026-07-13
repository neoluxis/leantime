<?php

namespace Leantime\Domain\Api\Controllers;

use Leantime\Core\Controller\Controller;
use Leantime\Domain\Files\Services\Files as FileService;
use Symfony\Component\HttpFoundation\Response;

/**
 * FileManager API — browse, create, rename, delete files and folders.
 */
class FileManager extends Controller
{
    private FileService $fileService;

    public function init(FileService $fileService): void
    {
        $this->fileService = $fileService;
    }

    /**
     * GET — read file content or list directory.
     */
    public function get(array $params): Response
    {
        // Read file content
        if (isset($_GET['action']) && $_GET['action'] === 'read' && isset($_GET['fileId'])) {
            $content = $this->fileService->readFileContent((int) $_GET['fileId']);
            if ($content === false) {
                return $this->tpl->displayJson(['error' => 'File not found'], 404);
            }

            $file = $this->fileService->getFilesByModule('', null, null);
            // Use repository directly for single file lookup
            $fileRepo = app()->make(\Leantime\Domain\Files\Repositories\Files::class);
            $fileData = $fileRepo->getFile((int) $_GET['fileId']);

            return $this->tpl->displayJson([
                'id' => (int) $_GET['fileId'],
                'name' => ($fileData['realName'] ?? 'file').'.'.($fileData['extension'] ?? 'txt'),
                'content' => $content,
                'extension' => $fileData['extension'] ?? 'txt',
            ]);
        }

        return $this->tpl->displayJson(['error' => 'Unknown action'], 400);
    }

    /**
     * POST — list, create, rename, delete, move.
     */
    public function post(array $params): Response
    {
        $action = $_POST['action'] ?? $_GET['action'] ?? '';

        return match ($action) {
            'list' => $this->listDir(),
            'createFolder' => $this->createFolder(),
            'createFile' => $this->createFile(),
            'rename' => $this->rename(),
            'delete' => $this->delete(),
            'move' => $this->move(),
            'write' => $this->write(),
            default => $this->tpl->displayJson(['error' => 'Unknown action: '.$action], 400),
        };
    }

    /**
     * List files and folders in a directory.
     */
    private function listDir(): Response
    {
        $module = $_POST['module'] ?? $_GET['module'] ?? 'project';
        $moduleId = (int) ($_POST['moduleId'] ?? $_GET['moduleId'] ?? session('currentProject', 0));
        $folderId = isset($_POST['folderId']) && $_POST['folderId'] !== ''
            ? (int) $_POST['folderId']
            : (isset($_GET['folderId']) && $_GET['folderId'] !== '' ? (int) $_GET['folderId'] : null);

        $folders = $this->fileService->getFolders($module, $moduleId, $folderId);
        $files = $this->fileService->getFilesByFolder($module, $moduleId, $folderId);

        // Build breadcrumbs
        $breadcrumbs = $this->buildBreadcrumbs($module, $moduleId, $folderId);

        return $this->tpl->displayJson([
            'folderId' => $folderId,
            'folders' => $folders,
            'files' => $files,
            'breadcrumbs' => $breadcrumbs,
            'module' => $module,
            'moduleId' => $moduleId,
        ]);
    }

    /**
     * Create a new folder.
     */
    private function createFolder(): Response
    {
        $module = $_POST['module'] ?? 'project';
        $moduleId = (int) ($_POST['moduleId'] ?? session('currentProject', 0));
        $name = trim($_POST['name'] ?? '');
        $parentId = isset($_POST['folderId']) && $_POST['folderId'] !== '' ? (int) $_POST['folderId'] : null;

        $folder = $this->fileService->createFolder($name, $module, $moduleId, $parentId);

        if ($folder) {
            return $this->tpl->displayJson(['success' => true, 'folder' => $folder]);
        }

        return $this->tpl->displayJson(['success' => false, 'error' => 'Could not create folder'], 400);
    }

    /**
     * Create a new empty text file.
     */
    private function createFile(): Response
    {
        $module = $_POST['module'] ?? 'project';
        $moduleId = (int) ($_POST['moduleId'] ?? session('currentProject', 0));
        $folderId = isset($_POST['folderId']) && $_POST['folderId'] !== '' ? (int) $_POST['folderId'] : null;
        $name = trim($_POST['name'] ?? 'untitled.md');

        $file = $this->fileService->createFile($name, $folderId, $module, $moduleId);

        if ($file) {
            return $this->tpl->displayJson(['success' => true, 'file' => $file]);
        }

        return $this->tpl->displayJson(['success' => false, 'error' => 'Could not create file'], 400);
    }

    /**
     * Rename a file or folder.
     */
    private function rename(): Response
    {
        $id = (int) ($_POST['id'] ?? 0);
        $type = $_POST['type'] ?? 'file';
        $name = trim($_POST['name'] ?? '');

        if (empty($name)) {
            return $this->tpl->displayJson(['success' => false, 'error' => 'Name is required'], 400);
        }

        if ($type === 'folder') {
            $result = $this->fileService->renameFolder($id, $name);
        } else {
            $result = $this->fileService->renameFile($id, $name);
        }

        return $this->tpl->displayJson(['success' => $result]);
    }

    /**
     * Delete a file or folder.
     */
    private function delete(): Response
    {
        $id = (int) ($_POST['id'] ?? 0);
        $type = $_POST['type'] ?? 'file';

        if ($type === 'folder') {
            $result = $this->fileService->deleteFolder($id);
        } else {
            $result = $this->fileService->deleteFile($id);
        }

        return $this->tpl->displayJson(['success' => $result]);
    }

    /**
     * Move a file to a different folder.
     */
    private function move(): Response
    {
        $fileId = (int) ($_POST['fileId'] ?? 0);
        $folderId = isset($_POST['folderId']) && $_POST['folderId'] !== '' ? (int) $_POST['folderId'] : null;

        $result = $this->fileService->moveFileToFolder($fileId, $folderId);

        return $this->tpl->displayJson(['success' => $result]);
    }

    /**
     * Write file content.
     */
    private function write(): Response
    {
        $fileId = (int) ($_POST['fileId'] ?? 0);
        $content = $_POST['content'] ?? '';

        $result = $this->fileService->writeFileContent($fileId, $content);

        return $this->tpl->displayJson([
            'success' => $result,
            'timestamp' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Build breadcrumb trail for a folder by walking up the parent chain.
     */
    private function buildBreadcrumbs(string $module, int $moduleId, ?int $currentFolderId): array
    {
        $breadcrumbs = [];

        if ($currentFolderId === null) {
            return $breadcrumbs;
        }

        // Walk up the parent chain
        $folderId = $currentFolderId;
        $chain = [];

        // Safety limit: max 20 levels deep
        for ($i = 0; $i < 20; $i++) {
            $repo = app()->make(\Leantime\Domain\Files\Repositories\Files::class);
            $folder = $repo->getFileFolderById($folderId);
            if (! $folder) {
                break;
            }
            array_unshift($chain, ['id' => (int) $folder['id'], 'name' => $folder['name']]);
            $folderId = $folder['parentId'] ?? null;
            if ($folderId === null) {
                break;
            }
        }

        return $chain;
    }
}
