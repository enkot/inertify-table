import { router as inertiaRouter } from "@inertiajs/vue3";
import {
  computed,
  reactive,
  unref,
  watch,
  type ComputedRef,
  type MaybeRef,
} from "vue";
import type {
  InertiaRouter,
  SortDirection,
  TableMeta,
  TableState,
} from "./types";

export interface UseTableOptions {
  url?: string;
  router?: InertiaRouter;
  only?: string[];
  preserveState?: boolean;
  preserveScroll?: boolean;
  replace?: boolean;
  autoSubmitFilters?: boolean;
  alwaysIncludePerPage?: boolean;
  transformQuery?: (query: Record<string, unknown>) => Record<string, unknown>;
}

export interface SetFilterOptions {
  submit?: boolean;
}

export interface UseTableApi {
  state: TableState;
  meta: ComputedRef<TableMeta>;
  query: ComputedRef<Record<string, unknown>>;
  selectedRows: ComputedRef<string[]>;
  selectionCount: ComputedRef<number>;
  visit: (overrides?: Record<string, unknown>) => void;
  setPage: (page: number, submit?: boolean) => void;
  setPerPage: (perPage: number, submit?: boolean) => void;
  setFilter: (key: string, value: unknown, options?: SetFilterOptions) => void;
  clearFilter: (key: string, submit?: boolean) => void;
  clearFilters: (submit?: boolean) => void;
  setSort: (
    key: string | null,
    direction?: Exclude<SortDirection, null> | null,
    submit?: boolean,
  ) => void;
  toggleSort: (key: string, submit?: boolean) => void;
  isSortedBy: (
    key: string,
    direction?: Exclude<SortDirection, null>,
  ) => boolean;
  isRowSelected: (rowKey: unknown) => boolean;
  setRowSelected: (rowKey: unknown, selected: boolean) => void;
  toggleRowSelected: (rowKey: unknown, selected?: boolean) => void;
  selectRows: (rowKeys: unknown[]) => void;
  clearSelection: () => void;
  areAllRowsSelected: (rowKeys: unknown[]) => boolean;
  areSomeRowsSelected: (rowKeys: unknown[]) => boolean;
  toggleAllRowsSelected: (rowKeys: unknown[], selected?: boolean) => void;
}

export function useTable(
  tableMeta: MaybeRef<TableMeta>,
  options: UseTableOptions = {},
): UseTableApi {
  const meta = computed(() => unref(tableMeta));

  const state = reactive<TableState>({
    page: meta.value.state.page,
    perPage: meta.value.state.perPage,
    sort: meta.value.state.sort,
    direction: meta.value.state.direction,
    filters: { ...meta.value.state.filters },
  });

  const selectedState = reactive<Record<string, boolean>>({});

  const selectedRows = computed(() =>
    Object.entries(selectedState)
      .filter(([, selected]) => selected)
      .map(([rowKey]) => rowKey),
  );
  const selectionCount = computed(() => selectedRows.value.length);

  watch(
    meta,
    (nextMeta) => {
      state.page = nextMeta.state.page;
      state.perPage = nextMeta.state.perPage;
      state.sort = nextMeta.state.sort;
      state.direction = nextMeta.state.direction;
      state.filters = { ...nextMeta.state.filters };

      clearSelection();
    },
    { deep: true },
  );

  const normalizedFilters = computed<Record<string, unknown>>(() => {
    const output: Record<string, unknown> = {};

    for (const [key, value] of Object.entries(state.filters)) {
      if (!isBlank(value)) {
        output[key] = value;
      }
    }

    return output;
  });

  const query = computed<Record<string, unknown>>(() => {
    const existing = existingQueryWithoutCurrentTable(meta.value);
    const currentQuery: Record<string, unknown> = {
      ...existing,
    };

    if (state.page > 1) {
      currentQuery[meta.value.queryKeys.page] = state.page;
    }

    const shouldIncludePerPage =
      options.alwaysIncludePerPage === true ||
      state.perPage !== meta.value.defaultPerPage;

    if (shouldIncludePerPage) {
      currentQuery[meta.value.queryKeys.perPage] = state.perPage;
    }

    const sortToken = toSortToken(state.sort, state.direction);
    if (sortToken !== null) {
      currentQuery[meta.value.queryKeys.sort] = sortToken;
    }

    if (Object.keys(normalizedFilters.value).length > 0) {
      currentQuery[meta.value.queryKeys.filters] = normalizedFilters.value;
    }

    return options.transformQuery
      ? options.transformQuery(currentQuery)
      : currentQuery;
  });

  function visit(overrides: Record<string, unknown> = {}): void {
    const url = options.url ?? currentPath();
    const router: InertiaRouter =
      options.router ?? (inertiaRouter as unknown as InertiaRouter);
    const data = {
      ...query.value,
      ...overrides,
    };

    router.get(url, data, {
      preserveState: options.preserveState ?? true,
      preserveScroll: options.preserveScroll ?? true,
      replace: options.replace ?? true,
      only: options.only,
    });
  }

  function setPage(page: number, submit = true): void {
    state.page = Math.max(1, Math.floor(page));

    if (submit) {
      visit();
    }
  }

  function setPerPage(perPage: number, submit = true): void {
    const normalized = Math.max(1, Math.floor(perPage));
    state.perPage = normalized;
    state.page = 1;

    if (submit) {
      visit();
    }
  }

  function setFilter(
    key: string,
    value: unknown,
    setFilterOptions: SetFilterOptions = {},
  ): void {
    state.filters[key] = value;
    state.page = 1;

    const submit =
      setFilterOptions.submit ?? options.autoSubmitFilters ?? false;

    if (submit) {
      visit();
    }
  }

  function clearFilter(key: string, submit = true): void {
    delete state.filters[key];
    state.page = 1;

    if (submit) {
      visit();
    }
  }

  function clearFilters(submit = true): void {
    state.filters = {};
    state.page = 1;

    if (submit) {
      visit();
    }
  }

  function setSort(
    key: string | null,
    direction: Exclude<SortDirection, null> | null = "asc",
    submit = true,
  ): void {
    if (key === null || direction === null) {
      state.sort = null;
      state.direction = null;
    } else {
      if (!meta.value.sorts.find((sort) => sort.key === key)) {
        return;
      }

      state.sort = key;
      state.direction = direction;
    }

    state.page = 1;

    if (submit) {
      visit();
    }
  }

  function toggleSort(key: string, submit = true): void {
    if (!meta.value.sorts.find((sort) => sort.key === key)) {
      return;
    }

    const defaultSort = parseSortToken(meta.value.defaultSort);
    const isDefaultSortKey = defaultSort?.key === key;

    if (state.sort !== key) {
      setSort(key, "asc", submit);

      return;
    }

    if (state.direction === "asc") {
      setSort(key, "desc", submit);

      return;
    }

    if (isDefaultSortKey) {
      setSort(key, "asc", submit);

      return;
    }

    setSort(null, null, submit);
  }

  function isSortedBy(
    key: string,
    direction?: Exclude<SortDirection, null>,
  ): boolean {
    if (state.sort !== key) {
      return false;
    }

    if (!direction) {
      return true;
    }

    return state.direction === direction;
  }

  function isRowSelected(rowKey: unknown): boolean {
    const normalized = normalizeRowKey(rowKey);

    return normalized !== null ? selectedState[normalized] === true : false;
  }

  function setRowSelected(rowKey: unknown, selected: boolean): void {
    const normalized = normalizeRowKey(rowKey);

    if (normalized === null) {
      return;
    }

    if (selected) {
      selectedState[normalized] = true;

      return;
    }

    delete selectedState[normalized];
  }

  function toggleRowSelected(rowKey: unknown, selected?: boolean): void {
    const normalized = normalizeRowKey(rowKey);

    if (normalized === null) {
      return;
    }

    const next = selected ?? !isRowSelected(normalized);
    setRowSelected(normalized, next);
  }

  function selectRows(rowKeys: unknown[]): void {
    clearSelection();

    for (const rowKey of rowKeys) {
      setRowSelected(rowKey, true);
    }
  }

  function clearSelection(): void {
    for (const rowKey of Object.keys(selectedState)) {
      delete selectedState[rowKey];
    }
  }

  function areAllRowsSelected(rowKeys: unknown[]): boolean {
    const normalized = normalizeRowKeys(rowKeys);

    if (normalized.length === 0) {
      return false;
    }

    return normalized.every((rowKey) => isRowSelected(rowKey));
  }

  function areSomeRowsSelected(rowKeys: unknown[]): boolean {
    const normalized = normalizeRowKeys(rowKeys);

    if (normalized.length === 0) {
      return false;
    }

    return (
      normalized.some((rowKey) => isRowSelected(rowKey)) &&
      !areAllRowsSelected(normalized)
    );
  }

  function toggleAllRowsSelected(rowKeys: unknown[], selected?: boolean): void {
    const normalized = normalizeRowKeys(rowKeys);

    if (normalized.length === 0) {
      return;
    }

    const next = selected ?? !areAllRowsSelected(normalized);

    for (const rowKey of normalized) {
      setRowSelected(rowKey, next);
    }
  }

  return {
    state,
    meta,
    query,
    selectedRows,
    selectionCount,
    visit,
    setPage,
    setPerPage,
    setFilter,
    clearFilter,
    clearFilters,
    setSort,
    toggleSort,
    isSortedBy,
    isRowSelected,
    setRowSelected,
    toggleRowSelected,
    selectRows,
    clearSelection,
    areAllRowsSelected,
    areSomeRowsSelected,
    toggleAllRowsSelected,
  };
}

function normalizeRowKey(rowKey: unknown): string | null {
  if (rowKey === null || rowKey === undefined || rowKey === "") {
    return null;
  }

  return String(rowKey);
}

function normalizeRowKeys(rowKeys: unknown[]): string[] {
  const values: string[] = [];

  for (const rowKey of rowKeys) {
    const normalized = normalizeRowKey(rowKey);

    if (normalized !== null) {
      values.push(normalized);
    }
  }

  return values;
}

function toSortToken(
  sort: string | null,
  direction: SortDirection,
): string | null {
  if (!sort || !direction) {
    return null;
  }

  return direction === "desc" ? `-${sort}` : sort;
}

function parseSortToken(
  token: string | null,
): { key: string; direction: Exclude<SortDirection, null> } | null {
  if (!token) {
    return null;
  }

  if (token.startsWith("-")) {
    const key = token.slice(1);

    if (key === "") {
      return null;
    }

    return {
      key,
      direction: "desc",
    };
  }

  return {
    key: token,
    direction: "asc",
  };
}

function isBlank(value: unknown): boolean {
  if (Array.isArray(value)) {
    return value.every((item) => isBlank(item));
  }

  return value === null || value === "";
}

function currentPath(): string {
  if (typeof window === "undefined") {
    return "/";
  }

  return window.location.pathname;
}

function existingQueryWithoutCurrentTable(
  meta: TableMeta,
): Record<string, unknown> {
  if (typeof window === "undefined") {
    return {};
  }

  const params = new URLSearchParams(window.location.search);
  const query: Record<string, unknown> = {};

  params.forEach((value, key) => {
    if (
      key === meta.queryKeys.page ||
      key === meta.queryKeys.perPage ||
      key === meta.queryKeys.sort ||
      key === meta.queryKeys.filters ||
      key.startsWith(`${meta.queryKeys.filters}[`)
    ) {
      return;
    }

    query[key] = value;
  });

  return query;
}
