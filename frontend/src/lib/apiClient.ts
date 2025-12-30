// A tiny mock API client that simulates production-like latency (200–400ms).
// Later you can swap `mockRequest()` with real fetch/axios without changing feature layers.

export type ApiErrorShape = {
  message: string;
  code?: string;
};

function randomIntInclusive(min: number, max: number): number {
  return Math.floor(Math.random() * (max - min + 1)) + min;
}

function sleep(ms: number): Promise<void> {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

export async function mockRequest<T>(handler: () => T): Promise<T> {
  const latency = randomIntInclusive(200, 400);
  await sleep(latency);

  try {
    return handler();
  } catch (error) {
    const message =
      error instanceof Error ? error.message : "Unexpected API error";
    const apiError: ApiErrorShape = { message };
    throw apiError;
  }
}
