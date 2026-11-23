import { spawnSync } from 'node:child_process';
import { readdirSync } from 'node:fs';
import { basename } from 'node:path';
import { fileURLToPath } from 'node:url';
import { isMainThread } from 'node:worker_threads';

const projectRoot = new URL('../', import.meta.url);
const themeDir = 'themes/dssast';
const wpContent = '/wordpress/wp-content';

function runNpm(args, env = process.env) {
  const npmCli = process.env.npm_execpath;
  if (!npmCli) throw new Error('Run this script with npm run playground.');

  const { error, status } = spawnSync(process.execPath, [npmCli, ...args], {
    cwd: projectRoot,
    env,
    stdio: 'inherit',
  });

  if (error) throw error;
  if (status !== 0) process.exit(status ?? 1);
}

function serve() {
  const pluginNames = readdirSync(new URL('plugins/', projectRoot), { withFileTypes: true })
    .filter((entry) => !entry.name.startsWith('.') && entry.name !== 'wordpress-importer')
    .filter((entry) => entry.isDirectory() || (entry.isFile() && entry.name.endsWith('.php')))
    .map((entry) => entry.name);

  const pluginMounts = pluginNames.flatMap((name) => [
    '--mount-dir', `plugins/${name}`, `${wpContent}/plugins/${name}`,
  ]);
  const pluginList = Buffer.from(JSON.stringify(pluginNames)).toString('base64');

  const env = { ...process.env };
  const forceColor = ['1', '2', '3', 'true'].includes(env.FORCE_COLOR);
  if (!process.stdout.isTTY && forceColor && !('NO_COLOR' in env)) {
    env.NODE_OPTIONS = [env.NODE_OPTIONS, `--import=${import.meta.url}`].filter(Boolean).join(' ');
  }

  runNpm([
    'exec', '--yes', '--', '@wp-playground/cli@3.1.65', 'server',
    '--php=8.5',
    '--port=9400',
    '--blueprint=playground/blueprint.json',
    '--blueprint-may-read-adjacent-files',
    '--define', 'DSSAST_PLAYGROUND_PLUGINS', pluginList,
    '--mount-dir', themeDir, `${wpContent}/${themeDir}`,
    ...pluginMounts,
  ], env);
}

function start() {
  runNpm(['--prefix', themeDir, 'ci']);
  runNpm(['run', 'build:theme']);
  runNpm([
    'exec', '--yes', '--', 'concurrently@9.2.5',
    '--kill-others',
    '--names', 'sass,wp',
    '--prefix-colors', 'cyan,green',
    `npm --prefix ${themeDir} run watch`,
    'npm run playground:serve',
  ]);
}

const entry = process.argv[1] ?? '';
if (isMainThread && entry === fileURLToPath(import.meta.url)) {
  switch (process.argv[2] ?? 'start') {
    case 'serve': serve(); break;
    case 'start': start(); break;
    default: throw new Error('Expected start or serve.');
  }
} else if (isMainThread && basename(entry) === 'wp-playground.js') {
  // The CLI uses isTTY for color and TERM for its cursor-based progress display.
  process.env.TERM = 'dumb';
  process.stdout.isTTY = true;
  process.stderr.isTTY = true;
}
