import { defineComponent } from "vue";
import { useHeadlessTableContext } from "../context";
import { useHeadlessTableSorting } from "../useHeadlessTableSorting";

export default defineComponent({
  name: "HeadlessTableSorting",
  setup(_, { slots }) {
    const table = useHeadlessTableContext();
    const sorting = useHeadlessTableSorting(table);

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
