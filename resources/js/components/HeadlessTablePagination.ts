import { defineComponent } from "vue";
import { useHeadlessTableContext } from "../context";
import { useHeadlessTablePagination } from "../useHeadlessTablePagination";

export default defineComponent({
  name: "HeadlessTablePagination",
  props: {
    window: {
      type: Number,
      default: 5,
    },
  },
  setup(props, { slots }) {
    const table = useHeadlessTableContext();
    const pagination = useHeadlessTablePagination(table, {
      window: props.window,
    });

    return () =>
      slots.default?.({
        page: pagination.page.value,
        lastPage: pagination.lastPage.value,
        pages: pagination.pages.value,
        hasPrevious: pagination.hasPrevious.value,
        hasNext: pagination.hasNext.value,
        perPage: pagination.perPage.value,
        perPageOptions: pagination.perPageOptions.value,
        setPage: pagination.setPage,
        setPerPage: pagination.setPerPage,
        previous: pagination.previous,
        next: pagination.next,
      }) ?? null;
  },
});
