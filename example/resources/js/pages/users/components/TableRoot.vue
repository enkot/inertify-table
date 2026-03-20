<script setup lang="ts">
import { computed } from "vue";
import { useInertifyTable } from "../../../../../../resources/js/index";
import { provideTableContext } from "./tableContext";
import type { TablePayload } from "./tableTypes";

const props = defineProps<{
    table: TablePayload;
}>();

const rows = computed(() => props.table.rows.data);
const total = computed(() => props.table.rows.total);
const from = computed(() => props.table.rows.from);
const to = computed(() => props.table.rows.to);

const table = useInertifyTable(computed(() => props.table.meta), {
    only: ["table"],
    replace: true,
});

provideTableContext({
    table,
    rows,
    total,
    from,
    to,
});
</script>

<template>
    <slot />
</template>
