import { computed, type ComputedRef } from "vue";
import { useHeadlessTableContext } from "./context";
import type { UseHeadlessTableApi } from "./useHeadlessTable";
import type { SortDirection, TableSort } from "./types";

export interface UseHeadlessTableSortingApi {
  sorts: ComputedRef<TableSort[]>;
  activeSort: ComputedRef<string | null>;
  activeDirection: ComputedRef<SortDirection>;
  toggleSort: (key: string, submit?: boolean) => void;
  setSort: (
    key: string | null,
    direction?: Exclude<SortDirection, null> | null,
    submit?: boolean,
  ) => void;
  isSortedBy: (
    key: string,
    direction?: Exclude<SortDirection, null>,
  ) => boolean;
}

export function useHeadlessTableSorting(
  tableApi?: UseHeadlessTableApi,
): UseHeadlessTableSortingApi {
  const table = tableApi ?? useHeadlessTableContext();

  const sorts = computed(() => table.meta.value.sorts);
  const activeSort = computed(() => table.state.sort);
  const activeDirection = computed(() => table.state.direction);

  return {
    sorts,
    activeSort,
    activeDirection,
    toggleSort: table.toggleSort,
    setSort: table.setSort,
    isSortedBy: table.isSortedBy,
  };
}
