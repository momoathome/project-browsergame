import type { Ref } from 'vue';
import type { Asteroid, Station, Rebel } from '@/types/types';

export interface ClickCoordinates {
    x: number;
    y: number;
}

export default function useAsteroidMapClick(
    canvasRef: Ref<HTMLCanvasElement | null>,
    ctx: Ref<CanvasRenderingContext2D | null>,
    pointX: Ref<number>,
    pointY: Ref<number>,
    zoomLevel: Ref<number>,
    scale: Ref<number>,
    asteroidBaseSize: number,
    stationBaseSize: number,
    rebelBaseSize: number,
    props: {
        asteroids: Asteroid[];
        stations: Station[];
        rebels: Rebel[];
    },
    startDrag: { x: number; y: number },
    isDragging: Ref<boolean>,
    scheduleDraw: () => void
) {
    function getClickCoordinates(e: MouseEvent): ClickCoordinates | null {
        const rect = canvasRef.value?.getBoundingClientRect();
        if (!rect || !ctx.value || !canvasRef.value) return null;

        const scaleX = canvasRef.value.width / rect.width;
        const scaleY = canvasRef.value.height / rect.height;

        const x = (e.clientX - rect.left) * scaleX;
        const y = (e.clientY - rect.top) * scaleY;

        return {
            x: (x - pointX.value) / zoomLevel.value,
            y: (y - pointY.value) / zoomLevel.value
        };
    }

    function findClickedAsteroid(coords: ClickCoordinates, radius = 120) {
        return props.asteroids.find(asteroid => {
            const dx = coords.x - asteroid.x;
            const dy = coords.y - asteroid.y;
            const distance = Math.sqrt(dx * dx + dy * dy);
            const scaledSize = (asteroidBaseSize * asteroid.pixel_size) * scale.value;
            return distance < Math.max(radius, scaledSize / 2);
        });
    }

    function findClickedStation(coords: ClickCoordinates, radius = 120) {
        return props.stations.find(station => {
            const dx = coords.x - station.x;
            const dy = coords.y - station.y;
            const distance = Math.sqrt(dx * dx + dy * dy);
            const scaledSize = stationBaseSize * scale.value;
            return distance < Math.max(radius, scaledSize / 2);
        });
    }

    function findClickedRebel(coords: ClickCoordinates, radius = 120) {
        return props.rebels.find(rebel => {
            const dx = coords.x - rebel.x;
            const dy = coords.y - rebel.y;
            const distance = Math.sqrt(dx * dx + dy * dy);
            const scaledSize = rebelBaseSize * scale.value;
            return distance < Math.max(radius, scaledSize / 2);
        });
    }

    // Drag & Move Funktionen
    function onMouseDown(e: MouseEvent) {
        isDragging.value = true;
        startDrag.x = e.clientX - pointX.value;
        startDrag.y = e.clientY - pointY.value;
    }

    function onMouseUp() {
        isDragging.value = false;
    }

    function onMouseMove(e: MouseEvent) {
        const rect = canvasRef.value?.getBoundingClientRect();
        if (!rect || !ctx.value || !canvasRef.value) return;

        if (isDragging.value) {
            pointX.value = e.clientX - startDrag.x;
            pointY.value = e.clientY - startDrag.y;
            scheduleDraw();
        }
    }

    return {
        getClickCoordinates,
        findClickedAsteroid,
        findClickedStation,
        findClickedRebel,
        onMouseDown,
        onMouseUp,
        onMouseMove,
    };
}
