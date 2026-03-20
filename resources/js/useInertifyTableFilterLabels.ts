import type {
  HeadlessFilterRule,
  UiFilter,
  UseInertifyTableFiltersApi,
} from "./useInertifyTableFilters";
import {
  toOperatorValue,
  toRangeValue,
  toText,
} from "./useInertifyTableFilters";

export interface UseInertifyTableFilterLabelsApi {
  ruleLabel: (rule: HeadlessFilterRule, filter: UiFilter | null) => string;
  summaryLabel: (filter: UiFilter, current: unknown) => string;
}

export function useInertifyTableFilterLabels(
  _filters: UseInertifyTableFiltersApi,
): UseInertifyTableFilterLabelsApi {
  function ruleLabel(
    rule: HeadlessFilterRule,
    filter: UiFilter | null,
  ): string {
    if (
      rule === "is" &&
      (filter?.control === "number-range" || filter?.control === "date-range")
    ) {
      return "is between";
    }

    if (
      rule === "is_not" &&
      (filter?.control === "number-range" || filter?.control === "date-range")
    ) {
      return "is not between";
    }

    if (rule === "is_not") {
      return "is not";
    }

    if (rule === "contains") {
      return "contains";
    }

    if (rule === "greater_than") {
      return "is greater than";
    }

    if (rule === "less_than") {
      return "is less than";
    }

    if (rule === "has_any_value") {
      return "has any value";
    }

    return "is";
  }

  function summaryLabel(filter: UiFilter, current: unknown): string {
    if (filter.control === "number-range" || filter.control === "date-range") {
      const normalizedRange = toRangeValue(current);
      const from = toText(normalizedRange.value.from);
      const to = toText(normalizedRange.value.to);

      if (normalizedRange.operator === "has_any_value") {
        return `${filter.label} has any value`;
      }

      if (normalizedRange.operator === "greater_than") {
        return from === ""
          ? filter.label
          : `${filter.label} is greater than ${from}`;
      }

      if (normalizedRange.operator === "less_than") {
        return to === "" ? filter.label : `${filter.label} is less than ${to}`;
      }

      if (normalizedRange.operator === "is_not") {
        if (from !== "" && to !== "") {
          return `${filter.label} is not between ${from} and ${to}`;
        }

        if (from !== "") {
          return `${filter.label} is less than ${from}`;
        }

        if (to !== "") {
          return `${filter.label} is greater than ${to}`;
        }
      }

      if (from !== "" && to !== "") {
        return `${filter.label} is between ${from} and ${to}`;
      }

      if (from !== "") {
        return `${filter.label} is greater than ${from}`;
      }

      if (to !== "") {
        return `${filter.label} is less than ${to}`;
      }
    }

    const normalized = toOperatorValue(current, filter);

    if (normalized.operator === "has_any_value") {
      return `${filter.label} has any value`;
    }

    const value = toText(normalized.value);

    if (value === "") {
      return `${filter.label}`;
    }

    if (normalized.operator === "is") {
      return `${filter.label} is ${value}`;
    }

    if (normalized.operator === "is_not") {
      return `${filter.label} is not ${value}`;
    }

    return `${filter.label} contains ${value}`;
  }

  return {
    ruleLabel,
    summaryLabel,
  };
}
