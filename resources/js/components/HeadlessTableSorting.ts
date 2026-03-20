import { defineComponent } from "vue";
import { useInertifyTableContext } from "../context";
import { useInertifyTableSorting } from "../useInertifyTableSorting";

export default defineComponent({
  name: "HeadlessTableSorting",
  setup(_, { slots }) {
    const table = useInertifyTableContext();
    const sorting = useInertifyTableSorting(table);

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
