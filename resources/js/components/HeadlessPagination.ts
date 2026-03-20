import {
  computed,
  defineComponent,
  toRef,
  type PropType,
} from "vue";
import { useHeadlessTable, type UseHeadlessTableOptions } from "../useHeadlessTable";
import type { TableMeta } from "../types";

type RouterOption = UseHeadlessTableOptions["router"];
type TransformQueryOption = UseHeadlessTableOptions["transformQuery"];

export default defineComponent({
  name: "HeadlessPagination",
  props: {
    meta: {
      type: Object as PropType<TableMeta>,
      required: true,
    },
    window: {
      type: Number,
      default: 5,
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

    const page = computed(() => props.meta.pagination?.page ?? api.state.page);
    const lastPage = computed(() => props.meta.pagination?.lastPage ?? 1);
    const hasPrevious = computed(() => page.value > 1);
    const hasNext = computed(() => page.value < lastPage.value);

    const pages = computed(() => {
      if (lastPage.value <= 1) {
        return [1];
      }

      const radius = Math.max(1, Math.floor(props.window / 2));
      const start = Math.max(1, page.value - radius);
      const end = Math.min(lastPage.value, start + props.window - 1);
      const adjustedStart = Math.max(1, end - props.window + 1);

      const values: number[] = [];
      for (let index = adjustedStart; index <= end; index += 1) {
        values.push(index);
      }

      return values;
    });

    function previous(): void {
      if (hasPrevious.value) {
        api.setPage(page.value - 1);
      }
    }

    function next(): void {
      if (hasNext.value) {
        api.setPage(page.value + 1);
      }
    }

    return () =>
      slots.default?.({
        page: page.value,
        lastPage: lastPage.value,
        pages: pages.value,
        hasPrevious: hasPrevious.value,
        hasNext: hasNext.value,
        previous,
        next,
        setPage: api.setPage,
      }) ?? null;
  },
});
