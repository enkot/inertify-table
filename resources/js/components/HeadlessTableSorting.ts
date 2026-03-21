import { defineComponent } from "vue";
import { useTableContext } from "../context";
import { useTableSorting } from "../useTableSorting";

export default defineComponent({
  name: "HeadlessTableSorting",
  setup(_, { slots }) {
    const table = useTableContext();
    const sorting = useTableSorting(table);

    return () =>
      slots.default?.({
        sorts: sorting.sorts.value,
        activeSort: sorting.activeSort.value,
        activeDirection: sorting.activeDirection.value,
        state: table.state,
        toggleSort: sorting.toggleSort,
        setSort: sorting.setSort,
        isSortedBy: sorting.isSortedBy,
      }) ?? null;
  },
});
