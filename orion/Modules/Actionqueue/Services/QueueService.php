<?php

namespace Orion\Modules\Actionqueue\Services;

use Illuminate\Support\Facades\App;
use Orion\Modules\Actionqueue\Enums\QueueActionType;
use Orion\Modules\Actionqueue\Enums\QueueStatusType;
use Orion\Modules\Actionqueue\Models\ActionQueue;
use Orion\Modules\Actionqueue\Handlers\BuildingUpgradeHandler;
use Orion\Modules\Actionqueue\Handlers\SpacecraftProductionHandler;
use Orion\Modules\Actionqueue\Handlers\AsteroidMiningHandler;
use Orion\Modules\Actionqueue\Handlers\CombatHandler;
use Orion\Modules\Actionqueue\Repositories\ActionqueueRepository;

class QueueService
{
    public function __construct(
        private readonly ActionqueueRepository $actionqueueRepository,
    ) {
    }
    public function getUserQueue($userId)
    {
        return $this->actionqueueRepository->getUserQueue($userId);
    }

    public function addToQueue($userId, $actionType, $targetId, $duration, $details)
    {
        return $this->actionqueueRepository->addToQueue($userId, $actionType, $targetId, $duration, $details);
    }

    public function getInProgressQueuesFromUserByType($userId, $actionType)
    {
        return $this->actionqueueRepository->getInProgressQueuesFromUserByType($userId, $actionType);
    }

    public function processQueue()
    {
        $completedActions = $this->actionqueueRepository->processQueue();

        foreach ($completedActions as $action) {
            $this->completeAction($action);
        }
    }

    public function processQueueForUser($userId)
    {
        $completedActions = $this->actionqueueRepository->processQueueForUser($userId);

        foreach ($completedActions as $action) {
            $this->completeAction($action);
        }
    }

    public function processQueueForUserInstant($userId)
    {
        // check for admin role
        $completedActions = $this->actionqueueRepository->processQueueForUserInstant($userId);

        foreach ($completedActions as $action) {
            $this->completeAction($action);
        }
    }

    private function completeAction(ActionQueue $action)
    {
        // Aktionstyp normalisieren - falls als String statt Enum
        $actionType = $action->action_type;
        
        if (is_string($actionType)) {
            // Versuche, den String in den entsprechenden Enum-Wert zu konvertieren
            try {
                $actionType = QueueActionType::from($action->action_type);
            } catch (\ValueError $e) {
                \Log::error("Ungültiger Aktionstyp", [
                    'action_id' => $action->id,
                    'action_type' => $action->action_type,
                    'error' => $e->getMessage()
                ]);
                $action->status = QueueStatusType::STATUS_FAILED;
                $action->save();
                return;
            }
        }
        
        // Handlerklassen für verschiedene Aktionstypen
        $handlerClass = match ($actionType) {
            QueueActionType::ACTION_TYPE_BUILDING => BuildingUpgradeHandler::class,
            QueueActionType::ACTION_TYPE_PRODUCE => SpacecraftProductionHandler::class,
            QueueActionType::ACTION_TYPE_MINING => AsteroidMiningHandler::class,
            QueueActionType::ACTION_TYPE_COMBAT => CombatHandler::class,
            default => null
        };
        
        if (!$handlerClass) {
            \Log::error("Kein Handler für Aktionstyp gefunden", [
                'action_id' => $action->id,
                'action_type' => $action->action_type,
                'normalized_type' => $actionType instanceof QueueActionType ? $actionType->value : $actionType
            ]);
            $action->status = QueueStatusType::STATUS_FAILED;
            $action->save();
            return;
        }
        
        $handler = App::make($handlerClass);
        
        try {
            $success = $handler->handle($action);
            
            \Log::info("Aktionsverarbeitung abgeschlossen", [
                'action_id' => $action->id,
                'action_type' => $action_type = $action->action_type,
                'success' => $success
            ]);
            
            if ($success) {
                $action->status = QueueStatusType::STATUS_COMPLETED;
            } else {
                $action->status = QueueStatusType::STATUS_FAILED;
            }
        } catch (\Exception $e) {
            \Log::error("Fehler bei der Aktionsverarbeitung", [
                'action_id' => $action->id,
                'action_type' => $action->action_type,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $action->status = QueueStatusType::STATUS_FAILED;
        }
        
        $action->save();
    }

}
