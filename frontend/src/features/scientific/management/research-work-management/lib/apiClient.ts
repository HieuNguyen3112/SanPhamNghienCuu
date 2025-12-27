export interface MockApiClientOptions {
  minDelayMs: number;
  maxDelayMs: number;
}

export interface MockApiClient {
  request<T>(requestName: string, executor: () => T): Promise<T>;
}

function createRandomDelay(minDelayMs: number, maxDelayMs: number): number {
  const safeMinDelayMs = Math.max(0, minDelayMs);
  const safeMaxDelayMs = Math.max(safeMinDelayMs, maxDelayMs);
  return (
    safeMinDelayMs +
    Math.floor(Math.random() * (safeMaxDelayMs - safeMinDelayMs + 1))
  );
}

export function createMockApiClient(
  options: MockApiClientOptions = { minDelayMs: 200, maxDelayMs: 400 }
): MockApiClient {
  return {
    request<T>(requestName: string, executor: () => T): Promise<T> {
      const delayMs = createRandomDelay(options.minDelayMs, options.maxDelayMs);

      return new Promise<T>((resolve, reject) => {
        setTimeout(() => {
          try {
            const result = executor();
            resolve(result);
          } catch (error) {
            const wrappedError = new Error(
              `[MockApiClient] Request failed: ${requestName}`
            );
            (wrappedError as any).cause = error;
            reject(wrappedError);
          }
        }, delayMs);
      });
    },
  };
}
