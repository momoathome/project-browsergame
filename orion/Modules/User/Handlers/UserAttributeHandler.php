<?php

namespace Orion\Modules\User\Handlers;

use Illuminate\Support\Collection;
use Orion\Modules\User\Enums\UserAttributeType;
use Orion\Modules\User\Repositories\UserAttributeRepository;
use Orion\Modules\Spacecraft\Services\SpacecraftService;

class UserAttributeHandler
{
    public function __construct(
        private readonly UserAttributeRepository $userAttributeRepository,
        private readonly SpacecraftService $spacecraftService
    ) {
    }

    /**
     * Berechnet und aktualisiert die Gesamtbesatzungskapazität für einen Benutzer
     *
     * @param int $userId Die Benutzer-ID
     * @param Collection|null $userAttributes Die Benutzerattribute, falls bereits geladen
     * @return Collection Die aktualisierten Benutzerattribute
     */
    public function updateTotalUnitsAttribute(int $userId, ?Collection $userAttributes = null): Collection
    {
        // Lade Attribute, wenn sie nicht übergeben wurden
        if ($userAttributes === null) {
            $userAttributes = $this->userAttributeRepository->getAllUserAttributesByUserId($userId);
        }

        // Raumschiffe für TOTAL_UNITS berechnen
        $spacecrafts = $this->spacecraftService->getAllSpacecraftsByUserId($userId);
    
        // Berechnung der Crew-Kapazität
        $totalCrewLimit = $spacecrafts->sum(function ($spacecraft) {
            $totalCount = $spacecraft->count;
            return $totalCount > 0 ? $spacecraft->crew_limit * $totalCount : 0;
        });
    
        // Nur TOTAL_UNITS aktualisieren, keine anderen Attribute ändern
        $totalUnitsAttribute = $userAttributes->where('attribute_name', UserAttributeType::TOTAL_UNITS->value)->first();
        if ($totalUnitsAttribute) {
            $totalUnitsAttribute->attribute_value = $totalCrewLimit;
        }

        return $userAttributes;
    }
}
