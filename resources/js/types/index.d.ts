import { route as routeFn } from 'tightenco/ziggy';

declare global {
    var route: typeof routeFn;
}

declare module 'vue' {
  interface ComponentCustomProperties {
      route: typeof routeFn;
  }
}
