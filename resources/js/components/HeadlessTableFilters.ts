import { defineComponent } from "vue";
import { useInertifyTableContext } from "../context";
import { useInertifyTableFilters } from "../useInertifyTableFilters";

export default defineComponent({
  name: "HeadlessTableFilters",
  setup(_, { slots }) {
    const table = useInertifyTableContext();
    const filters = useInertifyTableFilters(table);

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
