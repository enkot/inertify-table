import { inject, provide, type InjectionKey } from "vue";
import type { UseInertifyTableApi } from "./useInertifyTable";

const HeadlessTableContextKey: InjectionKey<UseInertifyTableApi> = Symbol(
  "HeadlessTableContext",
);

export function provideHeadlessTableContext(api: UseInertifyTableApi): void {
  provide(HeadlessTableContextKey, api);
}

export function tryUseInertifyTableContext(): UseInertifyTableApi | null {
  return inject(HeadlessTableContextKey, null);
}

export function useInertifyTableContext(): UseInertifyTableApi {
  const context = tryUseInertifyTableContext();

  if (!context) {
    throw new Error(
      "useInertifyTableContext must be used inside <HeadlessTableProvider>.",
    );
  }

  return context;
}
