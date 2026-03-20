import { computed, type ComputedRef } from "vue";
import { useInertifyTableContext } from "./context";
import type { UseInertifyTableApi } from "./useInertifyTable";

export interface UseInertifyTableSelectionApi {
  selectedRows: ComputedRef<string[]>;
  selectionCount: ComputedRef<number>;
  isRowSelected: (rowKey: unknown) => boolean;
  setRowSelected: (rowKey: unknown, selected: boolean) => void;
  toggleRowSelected: (rowKey: unknown, selected?: boolean) => void;
  selectRows: (rowKeys: unknown[]) => void;
  clearSelection: () => void;
  areAllRowsSelected: (rowKeys: unknown[]) => boolean;
  areSomeRowsSelected: (rowKeys: unknown[]) => boolean;
  toggleAllRowsSelected: (rowKeys: unknown[], selected?: boolean) => void;
}

export function useInertifyTableSelection(
  tableApi?: UseInertifyTableApi,
): UseInertifyTableSelectionApi {
  const table = tableApi ?? useInertifyTableContext();

  return {
    selectedRows: computed(() => table.selectedRows.value),
    selectionCount: computed(() => table.selectionCount.value),
    isRowSelected: table.isRowSelected,
    setRowSelected: table.setRowSelected,
    toggleRowSelected: table.toggleRowSelected,
    selectRows: table.selectRows,
    clearSelection: table.clearSelection,
    areAllRowsSelected: table.areAllRowsSelected,
    areSomeRowsSelected: table.areSomeRowsSelected,
    toggleAllRowsSelected: table.toggleAllRowsSelected,
  };
}
