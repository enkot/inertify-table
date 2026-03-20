import { defineComponent, toRef, type PropType } from "vue";
import { provideHeadlessTableContext } from "../context";
import { useHeadlessTable, type UseHeadlessTableOptions } from "../useHeadlessTable";
import type { TableMeta } from "../types";

type RouterOption = UseHeadlessTableOptions["router"];
type TransformQueryOption = UseHeadlessTableOptions["transformQuery"];

export default defineComponent({
  name: "HeadlessTableProvider",
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
    const api = useHeadlessTable(toRef(props, "meta"), {
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

    provideHeadlessTableContext(api);

    return () => slots.default?.(api) ?? null;
  },
});
