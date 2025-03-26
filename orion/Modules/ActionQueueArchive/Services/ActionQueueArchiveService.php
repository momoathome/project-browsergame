<?php

namespace Orion\Modules\ActionQueueArchive\Services;

use Orion\Modules\ActionQueueArchive\Repositories\ActionQueueArchiveRepository;

readonly class ActionQueueArchiveService
{

    public function __construct(
        private ActionQueueArchiveRepository $actionQueueArchiveRepository
    ) {
    }

    // Add service logic here
    public function createArchiveEntry($action)
    {
        return $this->actionQueueArchiveRepository->createArchiveEntry($action);}

    public function getArchiveEntriesByUserId($userId)
    {
        return $this->actionQueueArchiveRepository->getArchiveEntriesByUserId($userId);
    }

    public function getArchiveEntriesByUserIdAndActionType($userId, $actionType)
    {
        return $this->actionQueueArchiveRepository->getArchiveEntriesByUserIdAndActionType($userId, $actionType);
    }

    public function getAllArchiveEntries()
    {
        return $this->actionQueueArchiveRepository->getAllArchiveEntries();
    }
}
