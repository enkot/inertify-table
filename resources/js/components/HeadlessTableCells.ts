import { defineComponent, type PropType } from "vue";
import { tryUseInertifyTableContext } from "../context";
import type { UseInertifyTableApi } from "../useInertifyTable";
import type { TableColumn } from "../types";

export default defineComponent({
  name: "HeadlessTableCells",
  props: {
    table: {
      type: Object as PropType<UseInertifyTableApi>,
      required: false,
      default: undefined,
    },
    row: {
      type: Object as PropType<object>,
      required: true,
    },
    includeHidden: {
      type: Boolean,
      default: false,
    },
  },
  setup(props, { slots }) {
    const table = props.table ?? tryUseInertifyTableContext();

    if (!table) {
      throw new Error(
        "HeadlessTableCells requires either a `table` prop or <HeadlessTableProvider> context.",
      );
    }

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
        const row = props.row as Record<string, unknown>;
        const columnType = resolveColumnType(column);
        const slotByColumn = `column-${column.key}`;
        const slotByType = columnType ? `type-${columnType}` : null;
        const payload = {
          column,
          key: column.key,
          type: columnType,
          row,
          value: row[column.key],
        };

        const content =
          slots[slotByColumn]?.(payload) ??
          (slotByType ? slots[slotByType]?.(payload) : undefined) ??
          slots.default?.(payload);

        if (content) {
          return content;
        }

        const value = payload.value;

        if (value === null || value === undefined) {
          return "";
        }

        return String(value);
      });
  },
});
