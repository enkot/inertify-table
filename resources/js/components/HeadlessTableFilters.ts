import { defineComponent } from "vue";
import { useTableContext } from "../context";
import { useTableFilters } from "../useTableFilters";

export default defineComponent({
  name: "HeadlessTableFilters",
  setup(_, { slots }) {
    const table = useTableContext();
    const filters = useTableFilters(table);

    return () =>
      slots.default?.({
        filters: filters.filters.value,
        items: filters.items.value,
        active: filters.active.value,
        state: table.state,
        get: filters.get,
        set: filters.set,
        remove: filters.remove,
        apply: filters.apply,
        reset: filters.reset,
      }) ?? null;
  },
});
