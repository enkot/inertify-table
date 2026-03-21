import { computed, type ComputedRef } from "vue";
import { useTableContext } from "./context";
import type { UseTableApi } from "./useTable";
import type { TableFilter } from "./types";

export type HeadlessFilterRule =
  | "is"
  | "is_not"
  | "contains"
  | "greater_than"
  | "less_than"
  | "has_any_value";

export interface SelectOption {
  label: string;
  value: string;
}

export interface UiFilter extends TableFilter {
  control: "text" | "select" | "number-range" | "date-range";
  selectOptions: SelectOption[];
}

export interface OperatorFilterValue {
  operator?: HeadlessFilterRule;
  value?: unknown;
}

export interface RangeFilterValue {
  from?: unknown;
  to?: unknown;
}

export interface ActiveFilterPill {
  id: string;
  key: string;
  filter: UiFilter;
  value: unknown;
}

export interface UseTableFiltersApi {
  filters: ComputedRef<TableFilter[]>;
  items: ComputedRef<UiFilter[]>;
  active: ComputedRef<ActiveFilterPill[]>;
  get: (key: string) => unknown;
  set: (key: string, value: unknown, submit?: boolean) => void;
  remove: (key: string, submit?: boolean) => void;
  apply: () => void;
  reset: () => void;
}

export function useTableFilters(
  tableApi?: UseTableApi,
): UseTableFiltersApi {
  const table = tableApi ?? useTableContext();

  const filters = computed(() => table.meta.value.filters);
  const items = computed(() => buildUiFilters(filters.value));
  const active = computed<ActiveFilterPill[]>(() => {
    const pills: ActiveFilterPill[] = [];

    for (const filter of items.value) {
      const current = get(filter.key);

      if (isBlankFilterValue(current)) {
        continue;
      }

      pills.push({
        id: `${filter.key}`,
        key: filter.key,
        filter,
        value: current,
      });
    }

    return pills;
  });

  function get(key: string): unknown {
    return table.state.filters[key];
  }

  function set(key: string, value: unknown, submit = false): void {
    table.setFilter(key, value, { submit });
  }

  function remove(key: string, submit = false): void {
    table.clearFilter(key, submit);
  }

  function apply(): void {
    table.setPage(1, false);
    table.visit();
  }

  function reset(): void {
    table.clearFilters(false);
    table.visit();
  }

  return {
    filters,
    items,
    active,
    get,
    set,
    remove,
    apply,
    reset,
  };
}

export function toText(value: unknown): string {
  return value === undefined || value === null ? "" : String(value);
}

export function defaultRule(filter: UiFilter): HeadlessFilterRule {
  if (filter.control === "text") {
    return "contains";
  }

  return "is";
}

export function isBlankFilterValue(value: unknown): boolean {
  if (isOperatorFilterValue(value)) {
    if (value.operator === "has_any_value") {
      return false;
    }

    return isBlankFilterValue(value.value);
  }

  if (Array.isArray(value)) {
    return value.every((item) => isBlankFilterValue(item));
  }

  if (typeof value === "object" && value !== null) {
    return Object.values(value).every((item) => isBlankFilterValue(item));
  }

  return value === null || value === undefined || value === "";
}

export function toOperatorValue(
  value: unknown,
  filter: UiFilter,
): Required<OperatorFilterValue> {
  if (isClauseListValue(value)) {
    return toOperatorValue(value[0] ?? "", filter);
  }

  if (isOperatorFilterValue(value)) {
    const nextOperator = value.operator ?? defaultRule(filter);

    return {
      operator: nextOperator,
      value: value.value ?? "",
    };
  }

  return {
    operator: defaultRule(filter),
    value,
  };
}

export function toRangeValue(value: unknown): {
  operator: HeadlessFilterRule;
  value: RangeFilterValue;
} {
  if (isOperatorFilterValue(value)) {
    const operator = normalizeFilterRule(value.operator, "is");
    const range = isRangeValue(value.value) ? value.value : {};

    return {
      operator,
      value: {
        from: range.from,
        to: range.to,
      },
    };
  }

  if (isRangeValue(value)) {
    return {
      operator: "is",
      value,
    };
  }

  return {
    operator: "is",
    value: {},
  };
}

function isClauseListValue(value: unknown): value is OperatorFilterValue[] {
  return (
    Array.isArray(value) && value.every((item) => isOperatorFilterValue(item))
  );
}

function isOperatorFilterValue(value: unknown): value is OperatorFilterValue {
  return typeof value === "object" && value !== null && "operator" in value;
}

function isRangeValue(
  value: unknown,
): value is { from?: unknown; to?: unknown } {
  return typeof value === "object" && value !== null;
}

function normalizeFilterRule(
  value: unknown,
  fallback: HeadlessFilterRule,
): HeadlessFilterRule {
  if (
    value === "is" ||
    value === "is_not" ||
    value === "contains" ||
    value === "greater_than" ||
    value === "less_than" ||
    value === "has_any_value"
  ) {
    return value;
  }

  return fallback;
}

function buildUiFilters(filters: TableFilter[]): UiFilter[] {
  return filters.map((filter) => {
    const isSelect = filter.input === "select" || filter.input === "boolean";
    const isNumberRange = filter.input === "number-range";
    const isDateRange = filter.input === "date-range";
    const selectOptions =
      filter.input === "boolean"
        ? [
            { label: "True", value: "true" },
            { label: "False", value: "false" },
          ]
        : normalizeSelectOptions(filter.options);

    return {
      ...filter,
      control: isSelect
        ? "select"
        : isNumberRange
          ? "number-range"
          : isDateRange
            ? "date-range"
            : "text",
      selectOptions,
    };
  });
}

function normalizeSelectOptions(
  options: TableFilter["options"],
): SelectOption[] {
  return options.map((option) => {
    if (
      typeof option === "object" &&
      option !== null &&
      "label" in option &&
      "value" in option
    ) {
      const casted = option as { label: unknown; value: unknown };

      return {
        label: String(casted.label),
        value: String(casted.value),
      };
    }

    const value = String(option);

    return {
      label: humanize(value),
      value,
    };
  });
}

function humanize(value: string): string {
  const normalized = value.replace(/[_-]+/g, " ");

  return normalized.charAt(0).toUpperCase() + normalized.slice(1);
}
