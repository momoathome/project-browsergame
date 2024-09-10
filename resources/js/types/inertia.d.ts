import { Page } from "@inertiajs/core";
import type { UserResources, UserAttributes } from './types';

export { };

declare module '@inertiajs/vue3' {
  export function usePage<T extends inertia.Props = inertia.Props>(): Page<T>;
}

declare global {
  export namespace inertia {
    export interface Props {
      auth: Auth;
      jetstream: {
        [key: string]: boolean;
      };
      errorBags: unknown;
      errors: unknown;
      userResources: UserResources[];
      userAttributes: UserAttributes[];
    }
  }
}

interface User {
  id: number;
  name: string;
  email: string;
  email_verified_at: string | null;
  current_team_id: number | null;
  profile_photo_path: string | null;
  created_at: string;
  updated_at: string;
  two_factor_confirmed_at: string | null;
  profile_photo_url: string;
  two_factor_enabled: boolean;
  roles: string[];
  permissions: string[];
}

interface Auth {
  user: User;
}
