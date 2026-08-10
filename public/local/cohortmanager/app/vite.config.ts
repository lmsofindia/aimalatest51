import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from "node:path";

// https://vite.dev/config/
export default defineConfig({
  plugins: [vue()],
  define: {
    "process.env": {},
  },
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
  build: {
    emptyOutDir: false,
    outDir: path.resolve("../amd/build"),
    sourcemap: true,
    lib: {
      entry: path.resolve("./src/main.ts"),
      name: "cohort-manager-app",
      fileName: "app.min"
    },
    rollupOptions: {
      external: [
        "core/config",
        "core/ajax",
        "core/notification",
        "core/str",
        "core/toast",
        "core/templates",
        "core_form/modalform",
        'core/fragment',
        'core_form/dynamicform',
        'core_table/dynamic'
      ],
      output: {
        format: 'amd',
        amd: {
          id: 'local_cohortmanager/app'
        },
        entryFileNames: 'app.min.js',
        chunkFileNames: 'app.min.js',
        assetFileNames: 'app.min.[ext]',
        globals: {
          'core/config': 'core/config',
          'core/ajax': 'core/ajax',
          'core/notification': 'core/notification',
          'core/str': 'core/str',
          'core/toast': 'core/toast',
          "core/templates": "core/templates",
          "core_form/modalform": "core_form/modalform",
          'core/fragment': 'core/fragment',
          'core_form/dynamicform': 'core_form/dynamicform',
        }
      }
    }
  }
});
