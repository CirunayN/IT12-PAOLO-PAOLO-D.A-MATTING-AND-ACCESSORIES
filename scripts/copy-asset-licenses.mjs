import { copyFileSync, mkdirSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

const projectDirectory = fileURLToPath(new URL('../', import.meta.url));
const licenseDirectory = path.join(projectDirectory, 'public/assets/build/licenses');
mkdirSync(licenseDirectory, { recursive: true });

const licenses = {
    'inter.txt': '@fontsource-variable/inter/LICENSE',
    'outfit.txt': '@fontsource-variable/outfit/LICENSE',
    'dela-gothic-one.txt': '@fontsource/dela-gothic-one/LICENSE',
    'plus-jakarta-sans.txt': '@fontsource-variable/plus-jakarta-sans/LICENSE',
    'orbitron.txt': '@fontsource-variable/orbitron/LICENSE',
    'font-awesome.txt': '@fortawesome/fontawesome-free/LICENSE.txt',
    'vue.txt': '@vue/reactivity/LICENSE',
    'tailwind.txt': 'tailwindcss/LICENSE',
};

for (const [filename, source] of Object.entries(licenses)) {
    copyFileSync(path.join(projectDirectory, 'node_modules', source), path.join(licenseDirectory, filename));
}

// Alpine's npm package omits its license file; retain the upstream license here.
copyFileSync(path.join(projectDirectory, 'resources/licenses/alpine.txt'), path.join(licenseDirectory, 'alpine.txt'));
