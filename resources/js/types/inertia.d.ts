import type { PageProps as CorePageProps } from '@inertiajs/core';

declare module '@inertiajs/core' {
  interface PageProps extends CorePageProps {
    // **Preserve the index signature** from CorePageProps:
    [key: string]: any;

    // Then add your own shared props:
    flash: {
      success?: string;
      error?: string;
    };
    // …any other global props you want
  }
}

// re-export the core PageProps from the vue3 package:
declare module '@inertiajs/vue3' {
  export type PageProps = CorePageProps;
}
