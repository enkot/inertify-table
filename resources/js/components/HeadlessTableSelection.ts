import { defineComponent } from "vue";
import { useInertifyTableContext } from "../context";
import { useInertifyTableSelection } from "../useInertifyTableSelection";

export default defineComponent({
  name: "HeadlessTableSelection",
  setup(_, { slots }) {
    const table = useInertifyTableContext();
    const selection = useInertifyTableSelection(table);

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
