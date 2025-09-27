import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { QueueItem, ShipRenderObject, Station, Asteroid, SpacecraftFleet } from '@/types/types';

export default function useShipPool(
    queueData: any,
    userStation: any
) {
    const shipPool = ref<Map<number, ShipRenderObject>>(new Map());

    function updateShipPool() {
        const queueItems = (queueData.value || []) as QueueItem[];
        const currentTime = new Date().getTime();
        const activeMissionIds = new Set<number>();

        // 1. Sammle alle relevanten Missionen
        const missions = queueItems.filter((item) =>
            (item.actionType === 'mining' || item.actionType === 'combat') &&
            item.details?.target_coordinates !== undefined
        );

        // 2. Neue Missionen hinzufügen und bestehende aktualisieren
        if (missions.length > 0 && userStation.value) {
            missions.forEach(mission => {
                const missionId = mission.id;
                const missionType = mission.actionType as 'mining' | 'combat';
                activeMissionIds.add(missionId);

                // Nur neue Missionen initialisieren
                if (!shipPool.value.has(missionId)) {
                    initializeNewMission(mission, missionId, missionType);
                }
            });
        }

        // 3. Bestehende Missionen aktualisieren oder entfernen
        for (const missionId of shipPool.value.keys()) {
            if (!activeMissionIds.has(missionId)) {
                shipPool.value.delete(missionId);
            } else {
                updateMissionPosition(missionId, currentTime);
            }
        }
    }

    function initializeNewMission(mission: QueueItem, missionId: number, missionType: 'mining' | 'combat') {
        const targetCoords = mission.details.target_coordinates;
        const attackerCoords = mission.details.attacker_coordinates;
        const startTime = new Date(mission.startTime).getTime();
        const endTime = new Date(mission.endTime).getTime();

        // Zähle die Gesamtzahl der Schiffe basierend auf dem Missionstyp
        let totalShips = 0;

        if (missionType === 'mining') {
            const spacecrafts: SpacecraftFleet = mission.details.spacecrafts || {};
            totalShips = Object.values(spacecrafts).reduce(
                (sum, count) => sum + Number(count), 0
            );
        } else { // combat
            const attackerSpacecrafts = mission.details.attacker_formatted || [];
            totalShips = attackerSpacecrafts.reduce(
                (sum, spacecraft) => sum + Number(spacecraft.count), 0
            );
        }

        // Ermittle den Zielnamen je nach Missionstyp
        const targetName = missionType === 'mining'
            ? mission.details.asteroid_name || ''
            : mission.details.defender_name || 'Gegner';

        // Prüfe, ob Angriff auf mich
        const isAttackOnMe = mission.actionType === 'combat'
            && mission.targetId === usePage().props.auth.user.id;

        // Startkoordinaten setzen
        let startX, startY;
        if (isAttackOnMe && attackerCoords) {
            startX = attackerCoords.x;
            startY = attackerCoords.y;
        } else {
            startX = userStation.value!.x;
            startY = userStation.value!.y;
        }

        // Erstelle neues Objekt im Pool
        shipPool.value.set(missionId, {
            shipX: startX,
            shipY: startY,
            exactX: startX,
            exactY: startY,
            missionId,
            targetName,
            isAttackOnMe,
            totalShips,
            targetX: targetCoords!.x,
            targetY: targetCoords!.y,
            startX,
            startY,
            startTime,
            endTime,
            completed: false,
            textOffsetY: -30,
            missionType
        });
    }

    function updateMissionPosition(missionId: number, currentTime: number) {
        const shipObject = shipPool.value.get(missionId);
        if (!shipObject) return;

        const totalDuration = shipObject.endTime - shipObject.startTime;
        const elapsedDuration = currentTime - shipObject.startTime;
        const progressPercent = Math.min(Math.max(elapsedDuration / totalDuration, 0), 1);

        // Flugphasen berechnen
        if (progressPercent < 0.5) {
            // Hinflug: Start -> Ziel
            const flightProgress = progressPercent / 0.5;
            shipObject.exactX = shipObject.startX + (shipObject.targetX - shipObject.startX) * flightProgress;
            shipObject.exactY = shipObject.startY + (shipObject.targetY - shipObject.startY) * flightProgress;
        } else if (progressPercent < 1) {
            // Rückflug: Ziel -> Start
            const returnProgress = (progressPercent - 0.5) / 0.5;
            shipObject.exactX = shipObject.targetX + (shipObject.startX - shipObject.targetX) * returnProgress;
            shipObject.exactY = shipObject.targetY + (shipObject.startY - shipObject.targetY) * returnProgress;
        } else {
            // Mission abgeschlossen, Schiff am Start
            shipObject.exactX = shipObject.startX;
            shipObject.exactY = shipObject.startY;
            shipObject.shipX = shipObject.startX;
            shipObject.shipY = shipObject.startY;
            shipObject.completed = true;
            // Nach kurzer Zeit entfernen
            if (currentTime >= shipObject.endTime + 1000) {
                shipPool.value.delete(missionId);
                return;
            }
        }
        shipObject.shipX = shipObject.exactX;
        shipObject.shipY = shipObject.exactY;
    }

    function renderVisibleShips(ctx: CanvasRenderingContext2D, visibleArea: any, currentScale: number) {
        const buffer = 50 * currentScale;

        for (const ship of shipPool.value.values()) {
            if (!isShipVisible(ship, visibleArea, buffer)) continue;
            if (!ship.completed) {
                drawShip(ctx, ship, currentScale);
                drawShipLabel(ctx, ship, currentScale);
            }
        }
    }

    function isShipVisible(ship: ShipRenderObject, visibleArea: any, buffer: number) {
        return ship.shipX >= visibleArea.left - buffer &&
            ship.shipX <= visibleArea.right + buffer &&
            ship.shipY >= visibleArea.top - buffer &&
            ship.shipY <= visibleArea.bottom + buffer;
    }

    function drawShip(ctx: CanvasRenderingContext2D, ship: ShipRenderObject, currentScale: number) {
        const displayX = Math.round(ship.shipX);
        const displayY = Math.round(ship.shipY);

        if (ship.missionType === 'combat' && ship.isAttackOnMe) {
            ctx.fillStyle = 'rgba(255, 0, 0, 1)';
        } else if (ship.missionType === 'combat') {
            ctx.fillStyle = 'rgba(0, 255, 255, 1)';
        } else {
            ctx.fillStyle = 'white';
        }

        ctx.beginPath();
        ctx.arc(displayX, displayY, 20 * currentScale, 0, 2 * Math.PI);
        ctx.fill();
    }

    function drawShipLabel(ctx: CanvasRenderingContext2D, ship: ShipRenderObject, currentScale: number) {
        const labelText = ship.missionType === 'combat'
            ? `${ship.targetName} (Attack)`
            : ship.targetName;

        const textWidth = ctx.measureText(labelText).width;
        const textX = ship.exactX - textWidth / 2;
        const textY = ship.exactY + ship.textOffsetY * currentScale;

        if (ship.missionType === 'combat' && ship.isAttackOnMe) {
            ctx.fillStyle = 'rgba(255, 0, 0, 1)';
        } else if (ship.missionType === 'combat') {
            ctx.fillStyle = 'rgba(0, 255, 255, 1)';
        } else {
            ctx.fillStyle = 'white';
        }

        ctx.fillText(labelText, textX, textY);
    }

    return {
        shipPool,
        updateShipPool,
        initializeNewMission,
        updateMissionPosition,
        renderVisibleShips,
    };
}
