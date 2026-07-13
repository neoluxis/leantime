<?php

namespace Leantime\Domain\Files\Services;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Log;
use Leantime\Core\Events\DispatchesEvents;
use Leantime\Core\Files\Exceptions\FileValidationException;
use Leantime\Core\Files\FileManager;
use Leantime\Core\Language as LanguageCore;
use Leantime\Domain\Auth\Models\Roles;
use Leantime\Domain\Auth\Services\Auth;
use Leantime\Domain\Files\Repositories\Files as FileRepository;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

/**
 * @api
 */
class Files
{
    use DispatchesEvents;

    public function __construct(
        protected FileRepository $fileRepository,
        protected FileManager $fileManager,
        protected LanguageCore $language,
    ) {}

    /**
     * @api
     */
    public function getFilesByModule(string $module = '', $entityId = null, $userId = null): false|array
    {
        return $this->fileRepository->getFilesByModule($module, $entityId, $userId);
    }

    /**
     * @throws BindingResolutionException
     *
     * @api
     */
    public function upload($file, $module, $moduleId, $entity = null, $disk = 'default', ?int $folderId = null): array|string
    {
        try {
            // Validate input parameters
            if (empty($module) || empty($moduleId)) {
                Log::warning('Upload attempted with missing module or moduleId', [
                    'module' => $module,
                    'moduleId' => $moduleId,
                ]);
                throw new FileValidationException('Missing module or moduleId', FileValidationException::VALIDATION_ERROR);
            }

            if (! isset($file['file']) || ! is_array($file['file'])) {
                throw new FileNotFoundException('File not included in request or has invalid format');
            }
        } catch (FileValidationException $e) {
            Log::warning('File validation failed: '.$e->getMessage());

            return $e->getUserMessage();
        }

        // Normalize module names for consistency
        if ($module === 'projects') {
            $module = 'project';
        }
        if ($module === 'tickets') {
            $module = 'ticket';
        }

        try {
            // Validate file type with the enhanced validator
            $symfonyFile = new UploadedFile(
                $file['file']['tmp_name'],
                $file['file']['name'],
                $file['file']['type'],
                $file['file']['error'],
                true
            );

            // Validate file size before processing
            if ($file['file']['size'] > FileManager::getMaximumFileUploadSize()) {
                throw new FileValidationException('File exceeds maximum allowed size', FileValidationException::FILE_TOO_LARGE);
            }
        } catch (FileValidationException $e) {
            Log::warning('File validation failed: '.$e->getMessage());

            return $e->getUserMessage();
        }

        try {
            // Create a UploadedFile instance
            $symfonyFile = new UploadedFile(
                $file['file']['tmp_name'],
                $file['file']['name'],
                $file['file']['type'],
                $file['file']['error'],
                (bool) config('app.debug')
            );

            $leantimeFile = $this->fileManager->upload($symfonyFile, $disk);
        } catch (\Exception $e) {
            return 'Error uploading file: '.$e->getMessage();
        }

        if ($leantimeFile) {
            $leantimeFile['module'] = $module;
            $leantimeFile['moduleId'] = $moduleId;
            $leantimeFile['folderId'] = $folderId;

            $fileAddResults = $this->fileRepository->addFile($leantimeFile, $module);

            if ($fileAddResults) {
                $leantimeFile['fileId'] = $fileAddResults;

                return $leantimeFile;
            }
        }

        return false;
    }

    public function getModules($id): array
    {
        $modules = $this->fileRepository->userModules;
        if (Auth::userIsAtLeast(Roles::$admin)) {
            $modules = $this->fileRepository->adminModules;
        }

        return $modules;
    }

    /**
     * @api
     */
    public function deleteFile($fileId): bool
    {
        return $this->fileRepository->deleteFile($fileId);
    }

    public function getFilePathById($fileId): false|string
    {
        $dbReference = $this->fileRepository->getFile($fileId);
        if ($dbReference) {
            return $this->fileManager->getFileUrl($dbReference['encName'].'.'.$dbReference['extension']);
        }

        return false;
    }

    public function getFileById($fileId): false|Response
    {
        $dbReference = $this->fileRepository->getFile($fileId);
        if ($dbReference) {
            return $this->fileManager->getFile($dbReference['encName'].'.'.$dbReference['extension'], $dbReference['realName']);
        }

        return false;
    }

    // ── Folder management ────────────────────────────────────────────

    /**
     * Get folders for a module + moduleId, optionally filtered by parent.
     */
    public function getFolders(string $module, int $moduleId, ?int $parentId = null): array
    {
        return $this->fileRepository->getFileFolders($module, $moduleId, $parentId);
    }

    /**
     * Get files filtered by folder.
     */
    public function getFilesByFolder(string $module, int $moduleId, ?int $folderId): array
    {
        return $this->fileRepository->getFilesByFolder($module, $moduleId, $folderId);
    }

    /**
     * Create a new folder — requires editor+.
     */
    public function createFolder(string $name, string $module, int $moduleId, ?int $parentId = null): array|false
    {
        if (! Auth::userIsAtLeast(Roles::$editor)) {
            return false;
        }

        if (empty(trim($name))) {
            return false;
        }

        $id = $this->fileRepository->addFileFolder($name, $module, $moduleId, $parentId);
        if ($id) {
            $folder = $this->fileRepository->getFileFolderById($id);

            return $folder;
        }

        return false;
    }

    /**
     * Rename a folder — requires editor+.
     */
    public function renameFolder(int $folderId, string $name): bool
    {
        if (! Auth::userIsAtLeast(Roles::$editor)) {
            return false;
        }

        return $this->fileRepository->renameFileFolder($folderId, $name);
    }

    /**
     * Delete a folder — requires editor+, folders with files are allowed (files go to root).
     */
    public function deleteFolder(int $folderId): bool
    {
        if (! Auth::userIsAtLeast(Roles::$editor)) {
            return false;
        }

        return $this->fileRepository->deleteFileFolder($folderId);
    }

    /**
     * Move a file to a folder — requires editor+.
     */
    public function moveFileToFolder(int $fileId, ?int $folderId): bool
    {
        if (! Auth::userIsAtLeast(Roles::$editor)) {
            return false;
        }

        return $this->fileRepository->moveFileToFolder($fileId, $folderId);
    }

    /**
     * Create an empty text file — requires editor+.
     */
    public function createFile(string $name, ?int $folderId, string $module, int $moduleId): array|false
    {
        if (! Auth::userIsAtLeast(Roles::$editor)) {
            return false;
        }

        $ext = pathinfo($name, PATHINFO_EXTENSION) ?: 'txt';
        $realName = pathinfo($name, PATHINFO_FILENAME);

        $encName = md5(session('userdata.id').time());
        $content = '';

        // Store empty file via FileManager
        $this->fileManager->write($encName.'.'.$ext, $content, 'default');

        $values = [
            'encName' => $encName,
            'realName' => $realName,
            'extension' => $ext,
            'moduleId' => $moduleId,
            'userId' => session('userdata.id'),
            'module' => $module,
            'folderId' => $folderId,
        ];

        $fileId = $this->fileRepository->addFile($values, $module);

        if ($fileId) {
            return $this->fileRepository->getFile((int) $fileId);
        }

        return false;
    }

    /**
     * Rename a file — requires editor+.
     */
    public function renameFile(int $fileId, string $newName): bool
    {
        if (! Auth::userIsAtLeast(Roles::$editor)) {
            return false;
        }

        $ext = pathinfo($newName, PATHINFO_EXTENSION) ?: 'txt';
        $realName = pathinfo($newName, PATHINFO_FILENAME);

        return $this->fileRepository->renameFile($fileId, $realName, $ext);
    }

    /**
     * Read text file content — requires editor+ for the owning project.
     */
    public function readFileContent(int $fileId): string|false
    {
        $file = $this->fileRepository->getFile($fileId);
        if (! $file) {
            return false;
        }

        $fileName = $file['encName'].'.'.$file['extension'];

        return $this->fileManager->read($fileName, 'default');
    }

    /**
     * Write text file content — requires editor+.
     */
    public function writeFileContent(int $fileId, string $content): bool
    {
        if (! Auth::userIsAtLeast(Roles::$editor)) {
            return false;
        }

        $file = $this->fileRepository->getFile($fileId);
        if (! $file) {
            return false;
        }

        $fileName = $file['encName'].'.'.$file['extension'];

        return $this->fileManager->write($fileName, $content, 'default');
    }
}
