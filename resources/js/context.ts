import { inject, provide, type InjectionKey } from "vue";
import type { UseTableApi } from "./useTable";

const HeadlessTableContextKey: InjectionKey<UseTableApi> = Symbol(
  "HeadlessTableContext",
);

export function provideHeadlessTableContext(api: UseTableApi): void {
  provide(HeadlessTableContextKey, api);
}

export function tryUseTableContext(): UseTableApi | null {
  return inject(HeadlessTableContextKey, null);
}

export function useTableContext(): UseTableApi {
  const context = tryUseTableContext();

  if (!context) {
    throw new Error(
      "useTableContext must be used inside <HeadlessTableProvider>.",
    );
  }

  return context;
}
