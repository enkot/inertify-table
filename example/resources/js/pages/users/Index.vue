<script setup lang="ts">
import TableBody from "./components/TableBody.vue";
import TableFilters from "./components/TableFilters.vue";
import TablePagination from "./components/TablePagination.vue";
import TableRoot from "./components/TableRoot.vue";
import type { TablePayload } from "./components/tableTypes";

const props = defineProps<{
  table: TablePayload;
}>();
</script>

<template>
  <div class="space-y-6 p-6">
    <h1 class="text-2xl font-semibold">Users</h1>

    <TableRoot :table="props.table">
      <TableFilters />
      <TableBody />
      <TablePagination />
    </TableRoot>
  </div>
</template>
