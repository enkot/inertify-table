import { defineComponent, type PropType } from "vue";
import { tryUseTableContext } from "../context";
import { useTableSorting } from "../useTableSorting";
import type { UseTableApi } from "../useTable";
import type { TableColumn } from "../types";

export default defineComponent({
  name: "HeadlessTableHead",
  props: {
    table: {
      type: Object as PropType<UseTableApi>,
      required: false,
      default: undefined,
    },
    includeHidden: {
      type: Boolean,
      default: false,
    },
  },
  setup(props, { slots }) {
    const table = props.table ?? tryUseTableContext();

    if (!table) {
      throw new Error(
        "HeadlessTableHead requires either a `table` prop or <HeadlessTableProvider> context.",
      );
    }

    const sorting = useTableSorting(table);

    const visibleColumns = (): TableColumn[] => {
      const columns = table.meta.value.columns;

      if (props.includeHidden) {
        return columns;
      }

      return columns.filter((column) => !column.hidden);
    };

    const normalizeSlotToken = (value: unknown): string | null => {
      if (typeof value !== "string") {
        return null;
      }

      const normalized = value
        .trim()
        .toLowerCase()
        .replace(/\s+/g, "-")
        .replace(/[^a-z0-9_-]/g, "");

      return normalized.length > 0 ? normalized : null;
    };

    const resolveColumnType = (column: TableColumn): string | null => {
      const metaType = normalizeSlotToken(column.meta?.type);

      if (metaType) {
        return metaType;
      }

      const filter = table.meta.value.filters.find(
        (item) => item.key === column.key || item.column === column.key,
      );

      if (!filter) {
        return null;
      }

      if (filter.input === "number-range") {
        return "number";
      }

      if (filter.input === "date-range") {
        return "date";
      }

      return normalizeSlotToken(filter.input);
    };

    return () =>
      visibleColumns().map((column) => {
        const columnType = resolveColumnType(column);
        const slotByColumn = `column-${column.key}`;
        const slotByType = columnType ? `type-${columnType}` : null;
        const payload = {
          column,
          key: column.key,
          type: columnType,
          label: column.label,
          sortable: column.sortable,
          isSorted: sorting.isSortedBy(column.key),
          direction:
            table.state.sort === column.key ? table.state.direction : null,
          toggleSort: () => sorting.toggleSort(column.key),
          setSort: sorting.setSort,
        };

        const content =
          slots[slotByColumn]?.(payload) ??
          (slotByType ? slots[slotByType]?.(payload) : undefined) ??
          slots.default?.(payload);

        if (content) {
          return content;
        }

        return column.label;
      });
  },
});
