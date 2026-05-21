import { createAtumViteConfig } from './build/create-atum-vite-config.mjs';
import { atumBaseOptions } from './build/atum-base-options.mjs';

// Dev / serve only. Production builds go through `bun build/build.mjs`.
export default createAtumViteConfig( atumBaseOptions );
