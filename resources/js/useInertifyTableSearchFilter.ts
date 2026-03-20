import {
  computed,
  onScopeDispose,
  ref,
  watch,
  type ComputedRef,
  type Ref,
} from "vue";
import type {
  UiFilter,
  UseInertifyTableFiltersApi,
} from "./useInertifyTableFilters";
import { toOperatorValue, toText } from "./useInertifyTableFilters";

export interface UseInertifyTableSearchFilterOptions {
  preferredKeys?: string[];
  debounceMs?: number;
}

export interface UseInertifyTableSearchFilterApi {
  searchTerm: Ref<string>;
  searchFilter: ComputedRef<UiFilter | null>;
}

export function useInertifyTableSearchFilter(
  filters: UseInertifyTableFiltersApi,
  options: UseInertifyTableSearchFilterOptions = {},
): UseInertifyTableSearchFilterApi {
  const preferredKeys = options.preferredKeys ?? ["name"];
  const debounceMs = options.debounceMs ?? 300;

  const searchTerm = ref("");
  const isSyncingSearchTerm = ref(false);
  let searchDebounceTimer: ReturnType<typeof setTimeout> | null = null;

  const searchFilter = computed<UiFilter | null>(() => {
    const textFilters = filters.items.value.filter(
      (filter) => filter.control === "text",
    );

    for (const key of preferredKeys) {
      const preferredFilter = textFilters.find((filter) => filter.key === key);

      if (preferredFilter) {
        return preferredFilter;
      }
    }

    return textFilters[0] ?? null;
  });

  function syncSearchInputFromFilter(): void {
    const filter = searchFilter.value;

    if (!filter) {
      return;
    }

    const normalized = toOperatorValue(filters.get(filter.key), filter);

    isSyncingSearchTerm.value = true;
    searchTerm.value = toText(normalized.value);
    isSyncingSearchTerm.value = false;
  }

  function applySearchTerm(term: string): void {
    const filter = searchFilter.value;

    if (!filter) {
      return;
    }

    if (term.trim() === "") {
      filters.remove(filter.key, false);
    } else {
      filters.set(
        filter.key,
        {
          operator: "contains",
          value: term,
        },
        false,
      );
    }

    filters.apply();
  }

  watch(
    () => {
      const filter = searchFilter.value;

      if (!filter) {
        return null;
      }

      return filters.get(filter.key);
    },
    () => {
      syncSearchInputFromFilter();
    },
    { immediate: true, deep: true },
  );

  watch(
    searchTerm,
    (value) => {
      if (isSyncingSearchTerm.value) {
        return;
      }

      if (searchDebounceTimer) {
        clearTimeout(searchDebounceTimer);
      }

      searchDebounceTimer = setTimeout(() => {
        applySearchTerm(value);
      }, debounceMs);
    },
    { flush: "sync" },
  );

  onScopeDispose(() => {
    if (searchDebounceTimer) {
      clearTimeout(searchDebounceTimer);
    }
  });

  return {
    searchTerm,
    searchFilter,
  };
}
