import { defineComponent } from "vue";
import { useTableContext } from "../context";
import { useTableSelection } from "../useTableSelection";

export default defineComponent({
  name: "HeadlessTableSelection",
  setup(_, { slots }) {
    const table = useTableContext();
    const selection = useTableSelection(table);

    return () =>
      slots.default?.({
        state: table.state,
        selectedRows: selection.selectedRows.value,
        selectionCount: selection.selectionCount.value,
        isRowSelected: selection.isRowSelected,
        setRowSelected: selection.setRowSelected,
        toggleRowSelected: selection.toggleRowSelected,
        selectRows: selection.selectRows,
        clearSelection: selection.clearSelection,
        areAllRowsSelected: selection.areAllRowsSelected,
        areSomeRowsSelected: selection.areSomeRowsSelected,
        toggleAllRowsSelected: selection.toggleAllRowsSelected,
      }) ?? null;
  },
});
