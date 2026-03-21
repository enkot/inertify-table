import {
  computed,
  ref,
  watch,
  watchEffect,
  type ComputedRef,
  type Ref,
} from "vue";
import type {
  HeadlessFilterRule,
  UiFilter,
  UseTableFiltersApi,
} from "./useTableFilters";
import {
  defaultRule,
  isBlankFilterValue,
  toOperatorValue,
  toRangeValue,
  toText,
} from "./useTableFilters";

export interface UseDraftFilterEditorApi {
  draftFilterKey: Ref<string>;
  draftRule: Ref<HeadlessFilterRule>;
  draftTextValue: Ref<string>;
  draftSelectValue: Ref<string>;
  draftRangeFrom: Ref<string>;
  draftRangeTo: Ref<string>;
  addFilterOptions: ComputedRef<UiFilter[]>;
  draftFilter: ComputedRef<UiFilter | null>;
  availableDraftRules: ComputedRef<HeadlessFilterRule[]>;
  prepareAddDraft: () => void;
  clearFilterByKey: (key: string) => void;
  prepareEditDraft: (key: string) => void;
  syncDraftFromFilter: (key: string) => void;
  applyDraftFilter: () => void;
}

export function useDraftFilterEditor(
  filters: UseTableFiltersApi,
): UseDraftFilterEditorApi {
  const draftFilterKey = ref("");
  const draftRule = ref<HeadlessFilterRule>("is");
  const draftTextValue = ref("");
  const draftSelectValue = ref("");
  const draftRangeFrom = ref("");
  const draftRangeTo = ref("");

  const addFilterOptions = computed(() => filters.items.value);
  const draftFilter = computed(
    () =>
      filters.items.value.find(
        (filter) => filter.key === draftFilterKey.value,
      ) ?? null,
  );

  const availableDraftRules = computed<HeadlessFilterRule[]>(() => {
    if (!draftFilter.value) {
      return ["is"];
    }

    if (draftFilter.value.input === "text") {
      return ["is", "is_not", "contains", "has_any_value"];
    }

    if (draftFilter.value.input === "select") {
      return ["is", "is_not", "has_any_value"];
    }

    return ["is", "is_not", "greater_than", "less_than", "has_any_value"];
  });

  watchEffect(() => {
    const allFilters = filters.items.value;

    if (
      allFilters.length > 0 &&
      !allFilters.some((filter) => filter.key === draftFilterKey.value)
    ) {
      draftFilterKey.value = allFilters[0].key;
    }
  });

  watch(availableDraftRules, (rules) => {
    if (!rules.includes(draftRule.value)) {
      draftRule.value = rules[0] ?? "is";
    }
  });

  watch(
    draftFilterKey,
    (key) => {
      syncDraftFromFilter(key);
    },
    { immediate: true },
  );

  function prepareAddDraft(): void {
    const firstAddable = addFilterOptions.value[0] ?? filters.items.value[0];

    if (firstAddable) {
      draftFilterKey.value = firstAddable.key;
      syncDraftFromFilter(firstAddable.key);
    }
  }

  function clearFilterByKey(key: string): void {
    filters.remove(key, false);
    filters.apply();
  }

  function prepareEditDraft(key: string): void {
    draftFilterKey.value = key;
    syncDraftFromFilter(key);
  }

  function syncDraftFromFilter(key: string): void {
    const filter = filters.items.value.find(
      (candidate) => candidate.key === key,
    );

    if (!filter) {
      return;
    }

    const current = filters.get(filter.key);
    if (isBlankFilterValue(current)) {
      draftRule.value = defaultRule(filter);
      draftTextValue.value = "";
      draftSelectValue.value = "";
      draftRangeFrom.value = "";
      draftRangeTo.value = "";

      return;
    }

    const normalized = toOperatorValue(current, filter);
    draftRule.value = normalized.operator;

    if (filter.input === "text") {
      draftTextValue.value = toText(normalized.value);
      draftSelectValue.value = "";
      draftRangeFrom.value = "";
      draftRangeTo.value = "";

      return;
    }

    if (filter.input === "select") {
      draftSelectValue.value =
        normalized.value == null ? "" : String(normalized.value);
      draftTextValue.value = "";
      draftRangeFrom.value = "";
      draftRangeTo.value = "";

      return;
    }

    const normalizedRange = toRangeValue(current);
    draftRule.value = normalizedRange.operator;
    draftRangeFrom.value = toText(normalizedRange.value.from);
    draftRangeTo.value = toText(normalizedRange.value.to);
    draftTextValue.value = "";
    draftSelectValue.value = "";
  }

  function applyDraftFilter(): void {
    const filter = draftFilter.value;

    if (!filter) {
      return;
    }

    if (filter.input === "text") {
      filters.set(
        filter.key,
        {
          operator: draftRule.value,
          value: draftTextValue.value,
        },
        false,
      );
    } else if (filter.input === "select") {
      filters.set(
        filter.key,
        {
          operator: draftRule.value,
          value: draftSelectValue.value,
        },
        false,
      );
    } else {
      filters.set(
        filter.key,
        {
          operator: draftRule.value,
          value: {
            from: draftRangeFrom.value,
            to: draftRangeTo.value,
          },
        },
        false,
      );
    }

    filters.apply();
  }

  return {
    draftFilterKey,
    draftRule,
    draftTextValue,
    draftSelectValue,
    draftRangeFrom,
    draftRangeTo,
    addFilterOptions,
    draftFilter,
    availableDraftRules,
    prepareAddDraft,
    clearFilterByKey,
    prepareEditDraft,
    syncDraftFromFilter,
    applyDraftFilter,
  };
}

export const useTableDraftFilters = useDraftFilterEditor;
export type useTableDraftFiltersApi = UseDraftFilterEditorApi;
