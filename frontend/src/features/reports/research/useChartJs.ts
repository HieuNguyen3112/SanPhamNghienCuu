import { onBeforeUnmount, onMounted, watch, type Ref } from "vue";
import { Chart } from "chart.js/auto";
import type {
  ChartConfiguration,
  ChartConfigurationCustomTypesPerDataset,
  ChartTypeRegistry,
} from "chart.js";

type AnyChartConfig =
  | ChartConfiguration<keyof ChartTypeRegistry, any, unknown>
  | ChartConfigurationCustomTypesPerDataset<
      keyof ChartTypeRegistry,
      any,
      unknown
    >;

function hasTopLevelType(
  config: AnyChartConfig
): config is ChartConfiguration<keyof ChartTypeRegistry, any, unknown> {
  return typeof (config as any).type !== "undefined";
}

export function useChartJs(
  canvasRef: Ref<HTMLCanvasElement | null>,
  getConfig: () => AnyChartConfig
) {
  let chartInstance: Chart | null = null;

  const destroyChart = () => {
    chartInstance?.destroy();
    chartInstance = null;
  };

  const createChart = () => {
    const canvas = canvasRef.value;
    if (!canvas) return;

    const nextConfig = getConfig();
    destroyChart();

    chartInstance = new Chart(canvas, nextConfig as any);
  };

  const updateChart = () => {
    const canvas = canvasRef.value;
    if (!canvas) return;

    const nextConfig = getConfig();

    // If not created yet -> create
    if (!chartInstance) {
      chartInstance = new Chart(canvas, nextConfig as any);
      return;
    }

    const prevConfig = chartInstance.config as AnyChartConfig;

    // If config “kind” changes (customTypes vs normal) OR chart type changes -> recreate (safest)
    const prevHasType = hasTopLevelType(prevConfig);
    const nextHasType = hasTopLevelType(nextConfig);

    const typeChanged =
      prevHasType && nextHasType ? prevConfig.type !== nextConfig.type : false;

    if (prevHasType !== nextHasType || typeChanged) {
      chartInstance.destroy();
      chartInstance = new Chart(canvas, nextConfig as any);
      return;
    }

    // Normal update path (no touching chartInstance.config.type)
    if (nextConfig.data) {
      chartInstance.data = nextConfig.data as any;
    }

    // options cannot be undefined
    chartInstance.options = (nextConfig.options ??
      chartInstance.options) as any;

    chartInstance.update();
  };

  onMounted(() => {
    updateChart();
  });

  watch(
    () => getConfig(), // watch the returned config, not the function itself
    () => updateChart(),
    { deep: true }
  );

  onBeforeUnmount(() => {
    destroyChart();
  });

  return {
    recreate: createChart,
    destroy: destroyChart,
  };
}
