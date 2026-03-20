import { defineComponent } from "vue";
import { useHeadlessTableContext } from "../context";
import { useHeadlessTableSelection } from "../useHeadlessTableSelection";

export default defineComponent({
  name: "HeadlessTableSelection",
  setup(_, { slots }) {
    const table = useHeadlessTableContext();
    const selection = useHeadlessTableSelection(table);

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
