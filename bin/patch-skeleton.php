<?php

/**
 * Applies the template's changes to files that belong to the Laravel skeleton.
 * Each change is anchored on skeleton text; a missing anchor stops generation, so a skeleton
 * change surfaces in CI instead of producing a half-configured project.
 */

declare(strict_types=1);

function patch(string $file, string $search, string $replace): void
{
    $content = file_get_contents($file);
    if ($content === false || substr_count($content, $search) !== 1) {
        fwrite(STDERR, "patch-skeleton: anchor not found exactly once in {$file}:\n{$search}\n");
        exit(1);
    }
    file_put_contents($file, str_replace($search, $replace, $content));
}

// Request id on every request.
patch(
    'bootstrap/app.php',
    "use Illuminate\\Foundation\\Application;\n",
    "use App\\Http\\Middleware\\AssignRequestId;\nuse Illuminate\\Foundation\\Application;\n",
);
patch(
    'bootstrap/app.php',
    "    ->withMiddleware(function (Middleware \$middleware): void {\n        //\n",
    "    ->withMiddleware(function (Middleware \$middleware): void {\n        \$middleware->prepend(AssignRequestId::class);\n",
);

// Readiness check.
patch('routes/web.php', "use Illuminate\\Support\\Facades\\Route;\n", "use App\\Http\\Controllers\\HealthController;\nuse Illuminate\\Support\\Facades\\Route;\n");
file_put_contents('routes/web.php', "\nRoute::get('/health', HealthController::class)->name('health');\n", FILE_APPEND);

// Vite dev server inside Docker, served over HTTPS with the local certificate.
patch(
    'vite.config.js',
    "import { defineConfig } from 'vite';\n",
    "import fs from 'node:fs';\nimport { defineConfig } from 'vite';\n",
);
patch(
    'vite.config.js',
    "\nexport default defineConfig({\n",
    <<<'JS'

// Reuse the certificate from `make certs` so the HTTPS page can load the dev server without mixed content.
const cert = 'docker/conf/ssl/cert.pem';
const key = 'docker/conf/ssl/cert.key';
const https = fs.existsSync(cert) && fs.existsSync(key)
    ? { cert: fs.readFileSync(cert), key: fs.readFileSync(key) }
    : undefined;

export default defineConfig({

JS,
);
patch(
    'vite.config.js',
    "    server: {\n",
    "    server: {\n        host: '0.0.0.0',\n        port: 5173,\n        strictPort: true,\n        https,\n        hmr: { host: 'localhost' },\n        cors: true,\n",
);

// Environment defaults for the Docker setup.
$env = [
    '/^APP_NAME=.*$/m' => 'APP_NAME=laravel-base',
    '/^APP_URL=.*$/m' => 'APP_URL=https://localhost:8443',
    '/^MAIL_MAILER=.*$/m' => 'MAIL_MAILER=smtp',
    '/^MAIL_HOST=.*$/m' => 'MAIL_HOST=mail',
    '/^MAIL_PORT=.*$/m' => 'MAIL_PORT=1025',
    '/^REDIS_HOST=.*$/m' => 'REDIS_HOST=redis',
    '/^# DB_HOST=.*$/m' => "# MariaDB (docker compose --profile full): DB_CONNECTION=mariadb\n# DB_HOST=db",
    '/^# DB_USERNAME=.*$/m' => '# DB_USERNAME=laravel',
    '/^# DB_PASSWORD=.*$/m' => '# DB_PASSWORD=secret',
];
$content = (string) file_get_contents('.env.example');
foreach ($env as $pattern => $line) {
    $content = preg_replace($pattern, $line, $content, 1, $count);
    if ($count !== 1) {
        fwrite(STDERR, "patch-skeleton: {$pattern} not found in .env.example\n");
        exit(1);
    }
}
file_put_contents('.env.example', $content."\n# Docker\nTZ=UTC\nHTTP_PORT=8080\nHTTPS_PORT=8443\nMAILPIT_PORT=8025\nDB_ROOT_PASSWORD=root\n");

// Composer: project name, scripts for make test/lint/fix.
$composer = json_decode((string) file_get_contents('composer.json'), true, flags: JSON_THROW_ON_ERROR);
$composer['name'] = 'app/laravel-base';
$composer['description'] = 'A Laravel application.';
$composer['scripts']['test'] = ['@php artisan config:clear --ansi', '@php artisan test'];
$composer['scripts']['lint'] = ['pint --test', 'phpstan analyse --no-progress --memory-limit=1G'];
$composer['scripts']['fix'] = ['pint'];
$composer['config']['allow-plugins']['pestphp/pest-plugin'] = true;
file_put_contents('composer.json', json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n");
