import { defineComponent, toRef, type PropType } from "vue";
import {
  useInertifyTable,
  type UseInertifyTableOptions,
} from "../useInertifyTable";
import type { TableMeta } from "../types";

type RouterOption = UseInertifyTableOptions["router"];
type TransformQueryOption = UseInertifyTableOptions["transformQuery"];

export default defineComponent({
  name: "HeadlessTable",
  props: {
    meta: {
      type: Object as PropType<TableMeta>,
      required: true,
    },
    url: {
      type: String,
      required: false,
      default: undefined,
    },
    router: {
      type: Object as PropType<RouterOption>,
      required: false,
      default: undefined,
    },
    only: {
      type: Array as PropType<string[]>,
      required: false,
      default: undefined,
    },
    preserveState: {
      type: Boolean,
      default: true,
    },
    preserveScroll: {
      type: Boolean,
      default: true,
    },
    replace: {
      type: Boolean,
      default: true,
    },
    autoSubmitFilters: {
      type: Boolean,
      default: false,
    },
    alwaysIncludePerPage: {
      type: Boolean,
      default: false,
    },
    transformQuery: {
      type: Function as PropType<TransformQueryOption>,
      required: false,
      default: undefined,
    },
  },
  setup(props, { slots }) {
    const api = useInertifyTable(toRef(props, "meta"), {
      url: props.url,
      router: props.router,
      only: props.only,
      preserveState: props.preserveState,
      preserveScroll: props.preserveScroll,
      replace: props.replace,
      autoSubmitFilters: props.autoSubmitFilters,
      alwaysIncludePerPage: props.alwaysIncludePerPage,
      transformQuery: props.transformQuery,
    });

    return () =>
      slots.default?.({
        state: api.state,
        meta: api.meta,
        query: api.query,
        visit: api.visit,
        setPage: api.setPage,
        setPerPage: api.setPerPage,
        setFilter: api.setFilter,
        clearFilter: api.clearFilter,
        clearFilters: api.clearFilters,
        setSort: api.setSort,
        toggleSort: api.toggleSort,
        isSortedBy: api.isSortedBy,
        selectedRows: api.selectedRows,
        selectionCount: api.selectionCount,
        isRowSelected: api.isRowSelected,
        setRowSelected: api.setRowSelected,
        toggleRowSelected: api.toggleRowSelected,
        selectRows: api.selectRows,
        clearSelection: api.clearSelection,
        areAllRowsSelected: api.areAllRowsSelected,
        areSomeRowsSelected: api.areSomeRowsSelected,
        toggleAllRowsSelected: api.toggleAllRowsSelected,
      }) ?? null;
  },
});
