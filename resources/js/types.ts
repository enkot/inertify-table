export type SortDirection = "asc" | "desc" | null;

export interface TableQueryKeys {
  page: string;
  perPage: string;
  sort: string;
  filters: string;
}

export interface TableState {
  page: number;
  perPage: number;
  sort: string | null;
  direction: SortDirection;
  filters: Record<string, unknown>;
}

export interface TableColumn {
  key: string;
  label: string;
  sortable: boolean;
  filterable: boolean;
  hidden: boolean;
  meta: Record<string, unknown>;
}

export interface TableSort {
  key: string;
  label: string;
  column: string;
  direction: SortDirection;
}

export interface TableFilterOption {
  label: string;
  value: string | number | boolean;
}

export interface TableFilterRangeValue {
  from?: string | number | null;
  to?: string | number | null;
}

export type TableFilterInput =
  | "text"
  | "select"
  | "boolean"
  | "number-range"
  | "date-range"
  | string;

export interface TableFilter {
  key: string;
  label: string;
  column: string;
  input: TableFilterInput;
  multiple: boolean;
  options: Array<TableFilterOption | string | number>;
  default: unknown;
  value: TableFilterRangeValue | unknown;
  rangeMin?: string | number | null;
  rangeMax?: string | number | null;
  rangeStep?: number | null;
  hidden?: boolean;
}

export interface TablePagination {
  page: number;
  perPage: number;
  total: number;
  from: number | null;
  to: number | null;
  lastPage: number;
  hasMorePages: boolean;
}

export interface Paginator<TItem> {
  data: TItem[];
  total: number;
  from: number | null;
  to: number | null;
  current_page: number;
  last_page: number;
}

export interface TableMeta {
  name: string;
  queryKeys: TableQueryKeys;
  state: TableState;
  defaultSort: string | null;
  defaultPerPage: number;
  perPageOptions: number[];
  columns: TableColumn[];
  sorts: TableSort[];
  filters: TableFilter[];
  pagination: TablePagination | null;
}

export interface InertiaRouter {
  get: (
    url: string,
    data?: Record<string, unknown>,
    options?: Record<string, unknown>,
  ) => void;
}
