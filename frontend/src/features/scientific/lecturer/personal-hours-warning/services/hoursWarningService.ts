import type {
  HoursAlertActionSuggestionDTO,
  HoursAlertItemDTO,
  HoursAlertsSummaryDTO,
} from "../contracts/hoursWarning.contract";
import {
  hoursActionSuggestionsMockDTO,
  hoursAlertListMockDTO,
  hoursAlertsSummaryMockDTO,
} from "../mock-data/hoursWarning.mock";

function sleep(ms: number) {
  return new Promise<void>((resolve) => window.setTimeout(resolve, ms));
}

function randomLatencyMs() {
  // 200–400ms
  return 200 + Math.floor(Math.random() * 201);
}

/**
 * Mock service - replace with real API later:
 * - GET /api/lecturer/hours/alerts/summary
 * - GET /api/lecturer/hours/alerts
 * - GET /api/lecturer/hours/alerts/suggestions
 */
export async function loadHoursAlertsSummaryDTO(): Promise<HoursAlertsSummaryDTO> {
  await sleep(randomLatencyMs());
  return hoursAlertsSummaryMockDTO;
}

export async function loadHoursAlertListDTO(): Promise<HoursAlertItemDTO[]> {
  await sleep(randomLatencyMs());
  return hoursAlertListMockDTO;
}

export async function loadHoursActionSuggestionsDTO(): Promise<
  HoursAlertActionSuggestionDTO[]
> {
  await sleep(randomLatencyMs());
  return hoursActionSuggestionsMockDTO;
}
