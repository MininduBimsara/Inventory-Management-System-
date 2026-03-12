import { getToken } from "@/lib/session";

const API_BASE_URL =
  process.env.NEXT_PUBLIC_API_BASE_URL ?? "http://127.0.0.1:8000/api/v1";

type ApiRequestInit = RequestInit & {
  token?: string;
};

export async function apiRequest<T>(
  path: string,
  init: ApiRequestInit = {},
): Promise<T> {
  const token = init.token ?? getToken();
  const headers = new Headers(init.headers);
  const hasBody = init.body !== undefined && init.body !== null;

  if (token) {
    headers.set("Authorization", `Bearer ${token}`);
  }

  if (hasBody && !headers.has("Content-Type")) {
    headers.set("Content-Type", "application/json");
  }

  const response = await fetch(`${API_BASE_URL}${path}`, {
    ...init,
    headers,
  });

  const payload = (await response.json().catch(() => null)) as T | null;

  if (!response.ok) {
    const fallback = `Request failed with ${response.status}`;
    const message =
      (payload as { message?: string } | null)?.message ?? fallback;
    throw new Error(message);
  }

  if (payload === null) {
    throw new Error("Empty response from server.");
  }

  return payload;
}

export type PaginatedMeta = {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
};
