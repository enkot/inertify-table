import { computed, type ComputedRef } from "vue";
import { useTableContext } from "./context";
import type { UseTableApi } from "./useTable";
import type { SortDirection, TableSort } from "./types";

export interface UseTableSortingApi {
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

export function useTableSorting(
  tableApi?: UseTableApi,
): UseTableSortingApi {
  const table = tableApi ?? useTableContext();

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
