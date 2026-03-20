import { defineComponent } from "vue";
import { useInertifyTableContext } from "../context";
import { useInertifyTablePagination } from "../useInertifyTablePagination";

export default defineComponent({
  name: "HeadlessTablePagination",
  props: {
    window: {
      type: Number,
      default: 5,
    },
  },
  setup(props, { slots }) {
    const table = useInertifyTableContext();
    const pagination = useInertifyTablePagination(table, {
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
