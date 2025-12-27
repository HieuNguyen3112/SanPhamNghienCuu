export type ApiHandler<T> = () => T;

function wait(ms: number) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

function randomLatencyMs() {
  // 200–400ms
  return 200 + Math.floor(Math.random() * 201);
}

export async function mockApiRequest<T>(handler: ApiHandler<T>): Promise<T> {
  await wait(randomLatencyMs());
  return handler();
}
