<?php

namespace Leantime\Domain\Files\Hxcontrollers;

use Leantime\Core\Controller\HtmxController;
use Leantime\Domain\Files\Services\Files;

class Folders extends HtmxController
{
    protected static string $view = 'files::partials.folderSidebar';

    private Files $filesService;

    public function init(Files $filesService): void
    {
        $this->filesService = $filesService;
    }

    /**
     * GET — render the folder sidebar for the current project.
     */
    public function get()
    {
        $module = $_GET['module'] ?? 'project';
        $moduleId = (int) ($_GET['moduleId'] ?? session('currentProject', 0));
        $currentFolderId = isset($_GET['folderId']) ? (int) $_GET['folderId'] : null;

        $folders = $this->filesService->getFolders($module, $moduleId, $currentFolderId);

        $this->tpl->assign('folders', $folders);
        $this->tpl->assign('currentFolderId', $currentFolderId);
        $this->tpl->assign('module', $module);
        $this->tpl->assign('moduleId', $moduleId);

        // Trigger an event so the file grid refreshes
        $this->setHTMXEvent('{"lt:files:folder.changed": {"folderId": '.json_encode($currentFolderId).'}}');
    }

    /**
     * POST — create a new folder.
     */
    public function create()
    {
        $module = $_POST['module'] ?? 'project';
        $moduleId = (int) ($_POST['moduleId'] ?? session('currentProject', 0));
        $name = trim($_POST['name'] ?? '');
        $parentId = isset($_POST['folderId']) && $_POST['folderId'] !== '' ? (int) $_POST['folderId'] : null;

        $this->filesService->createFolder($name, $module, $moduleId, $parentId);

        // Re-render the sidebar
        $this->get();
    }

    /**
     * POST — rename a folder.
     */
    public function rename()
    {
        $folderId = (int) ($_POST['folderId'] ?? 0);
        $name = trim($_POST['name'] ?? '');

        $this->filesService->renameFolder($folderId, $name);

        $this->get();
    }

    /**
     * POST — delete a folder.
     */
    public function delete()
    {
        $folderId = (int) ($_POST['folderId'] ?? 0);

        $this->filesService->deleteFolder($folderId);

        $this->get();
    }
}
