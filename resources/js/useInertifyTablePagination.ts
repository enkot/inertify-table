import { computed, type ComputedRef } from "vue";
import { useInertifyTableContext } from "./context";
import type { UseInertifyTableApi } from "./useInertifyTable";

export interface UseInertifyTablePaginationApi {
  page: ComputedRef<number>;
  lastPage: ComputedRef<number>;
  hasPrevious: ComputedRef<boolean>;
  hasNext: ComputedRef<boolean>;
  pages: ComputedRef<number[]>;
  perPage: ComputedRef<number>;
  perPageOptions: ComputedRef<number[]>;
  setPage: (page: number, submit?: boolean) => void;
  setPerPage: (perPage: number, submit?: boolean) => void;
  previous: () => void;
  next: () => void;
}

export interface UseInertifyTablePaginationOptions {
  window?: number;
}

export function useInertifyTablePagination(
  tableOrOptions?: UseInertifyTableApi | UseInertifyTablePaginationOptions,
  options: UseInertifyTablePaginationOptions = {},
): UseInertifyTablePaginationApi {
  const table = isTableApi(tableOrOptions)
    ? tableOrOptions
    : useInertifyTableContext();
  const resolvedOptions = isTableApi(tableOrOptions)
    ? options
    : (tableOrOptions ?? {});
  const windowSize = resolvedOptions.window ?? 5;

  const page = computed(
    () => table.meta.value.pagination?.page ?? table.state.page,
  );
  const lastPage = computed(() => table.meta.value.pagination?.lastPage ?? 1);
  const hasPrevious = computed(() => page.value > 1);
  const hasNext = computed(() => page.value < lastPage.value);
  const perPage = computed(() => table.state.perPage);
  const perPageOptions = computed(() => table.meta.value.perPageOptions);

  const pages = computed(() => {
    if (lastPage.value <= 1) {
      return [1];
    }

    const radius = Math.max(1, Math.floor(windowSize / 2));
    const start = Math.max(1, page.value - radius);
    const end = Math.min(lastPage.value, start + windowSize - 1);
    const adjustedStart = Math.max(1, end - windowSize + 1);

    const values: number[] = [];
    for (let index = adjustedStart; index <= end; index += 1) {
      values.push(index);
    }

    return values;
  });

  function previous(): void {
    if (hasPrevious.value) {
      table.setPage(page.value - 1);
    }
  }

  function next(): void {
    if (hasNext.value) {
      table.setPage(page.value + 1);
    }
  }

  return {
    page,
    lastPage,
    hasPrevious,
    hasNext,
    pages,
    perPage,
    perPageOptions,
    setPage: table.setPage,
    setPerPage: table.setPerPage,
    previous,
    next,
  };
}

function isTableApi(value: unknown): value is UseInertifyTableApi {
  return (
    typeof value === "object" &&
    value !== null &&
    "state" in value &&
    "meta" in value
  );
}
