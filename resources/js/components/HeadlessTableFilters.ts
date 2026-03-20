import { defineComponent } from "vue";
import { useHeadlessTableContext } from "../context";
import { useHeadlessTableFilters } from "../useHeadlessTableFilters";

export default defineComponent({
  name: "HeadlessTableFilters",
  setup(_, { slots }) {
    const table = useHeadlessTableContext();
    const filters = useHeadlessTableFilters(table);

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
