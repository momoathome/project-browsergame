import type { Ref } from 'vue';
import type { Asteroid, Station, Rebel } from '@/types/types';

export default function useAnimateView(
  pointX: Ref<number>,
  pointY: Ref<number>,
  zoomLevel: Ref<number>,
  drawScene: () => void,
  props?: { asteroids?: Asteroid[]; stations?: Station[]; rebels?: Rebel[] },
  canvasRef?: Ref<HTMLCanvasElement | null>,
  config?: any
) {
  function animateView(
    targetX: number,
    targetY: number,
    targetZoomLevel: number
  ) {
    const startPointX = pointX.value;
    const startPointY = pointY.value;
    const startZoomLevel = zoomLevel.value;

    const endPointX = targetX;
    const endPointY = targetY;
    const endZoomLevel = targetZoomLevel;

    const distance = Math.sqrt(
      Math.pow(endPointX - startPointX, 2) + Math.pow(endPointY - startPointY, 2)
    );

    const maxAnimationDuration = 1500;
    const minAnimationDuration = 300;
    const animationDuration = Math.round(Math.max(
      minAnimationDuration,
      Math.min(maxAnimationDuration, distance / 8)
    ));

    const startTime = performance.now();

    function animate(time: number) {
      const elapsedTime = time - startTime;
      const progress = Math.min(elapsedTime / animationDuration, 1);

      pointX.value = startPointX + (endPointX - startPointX) * progress;
      pointY.value = startPointY + (endPointY - startPointY) * progress;
      zoomLevel.value = startZoomLevel + (endZoomLevel - startZoomLevel) * progress;

      drawScene();

      if (progress < 1) {
        requestAnimationFrame(animate);
      }
    }

    requestAnimationFrame(animate);
  }

  function focusOnObject(
    object: Station | Asteroid | Rebel | null,
    userId?: number
  ) {
    if (!props || !canvasRef?.value) return;

    let targetObject;
    if (userId) {
      targetObject = props.stations?.find(station => station.user_id === userId);
    } else if (object && 'faction' in object) {
      targetObject = props.rebels?.find(rebel => rebel.id === object.id);
    } else {
      targetObject = props.asteroids?.find(asteroid => asteroid.id === object.id);
    }

    if (!targetObject || !canvasRef.value) return;

    const targetX = -(targetObject.x * zoomLevel.value - canvasRef.value.width / 2);
    const targetY = -(targetObject.y * zoomLevel.value - canvasRef.value.height / 2);
    const targetZoomLevel = zoomLevel.value; // oder config?.baseZoomLevel

    animateView(targetX, targetY, targetZoomLevel);
  }

  return {
    animateView,
    focusOnObject
  };
}
