import type { Ref } from 'vue';

export default function useAnimateView(
  pointX: Ref<number>,
  pointY: Ref<number>,
  zoomLevel: Ref<number>,
  drawScene: () => void
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

  return {
    animateView
  };
}
