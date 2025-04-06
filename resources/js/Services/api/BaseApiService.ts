import type { ApiResponse } from '@/types/api';

export abstract class BaseApiService {
  protected async fetchWithError<T>(url: string, options: RequestInit): Promise<ApiResponse<T>> {
    try {
      const response = await fetch(url, {
        ...options,
        headers: this.getHeaders(),
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.message || 'Ein Fehler ist aufgetreten');
      }

      return { data };
    } catch (error) {
      console.error('API Fehler:', error);
      return {
        data: {} as T,
        error: error instanceof Error ? error.message : 'Ein unbekannter Fehler ist aufgetreten'
      };
    }
  }

  private getHeaders(): HeadersInit {
    return {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      'Content-Type': 'application/json',
    };
  }
}
