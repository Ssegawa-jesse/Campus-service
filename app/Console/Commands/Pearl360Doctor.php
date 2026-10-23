<?php

namespace App\Console\Commands;

use Dotenv\Dotenv;
use Illuminate\Console\Command;
use PDO;
use PDOException;

/**
 * Checks (and with --fix, repairs) everything Pearl360 needs to run locally:
 *   php artisan pearl360:doctor          – report only
 *   php artisan pearl360:doctor --fix    – create/repair .env, app key, database, folders
 *
 * It reads .env straight from disk, so it works even when .env is missing or broken.
 */
class Pearl360Doctor extends Command
{
    protected $signature = 'pearl360:doctor {--fix : Repair what can be repaired automatically}';

    protected $description = 'Check that PHP, .env, the app key and the database are set up correctly';

    private int $problems = 0;

    public function handle(): int
    {
        $fix = (bool) $this->option('fix');
        $base = base_path();
        $envPath = $base.DIRECTORY_SEPARATOR.'.env';

        $this->newLine();
        $this->line('<options=bold>Pearl360 setup check</>'.($fix ? ' (fixing problems)' : ''));
        $this->newLine();

        // 1. PHP version & extensions
        $this->check(PHP_VERSION_ID >= 80200, 'PHP '.PHP_VERSION, 'PHP 8.2 or newer is required.');
        foreach (['pdo', 'mbstring', 'openssl', 'tokenizer', 'ctype', 'fileinfo', 'curl', 'xml'] as $ext) {
            $this->check(extension_loaded($ext), "PHP extension: {$ext}", "Enable extension={$ext} in php.ini (C:\\xampp\\php\\php.ini), then restart.");
        }
        if (! extension_loaded('gd')) {
            $this->warnLine('PHP extension: gd (optional)', 'Recommended for uploading photos: remove the ; before extension=gd in php.ini and restart Apache.');
        } else {
            $this->ok('PHP extension: gd');
        }

        // 2. .env file
        if (! file_exists($envPath)) {
            foreach (['.env.txt', 'env', 'env.txt', '.env.example.txt'] as $wrong) {
                if (file_exists($base.DIRECTORY_SEPARATOR.$wrong) && $wrong !== '.env.example.txt') {
                    if ($fix) {
                        rename($base.DIRECTORY_SEPARATOR.$wrong, $envPath);
                        $this->fixed("Renamed {$wrong} to .env (Windows/Notepad had added the wrong name).");
                    } else {
                        $this->bad(".env file", "Found '{$wrong}' instead of '.env'. Rename it to .env (or run with --fix).");
                    }
                    break;
                }
            }
        }
        if (! file_exists($envPath)) {
            if ($fix && copy($base.DIRECTORY_SEPARATOR.'.env.example', $envPath)) {
                $this->fixed('Created .env from .env.example.');
            } else {
                $this->bad('.env file', 'Missing. Run: copy .env.example .env   (or run this command with --fix)');
                return $this->finish();
            }
        } else {
            $this->ok('.env file exists');
        }

        $env = Dotenv::parse((string) file_get_contents($envPath));

        // 3. Environment & debug mode
        if (($env['APP_ENV'] ?? 'production') !== 'local') {
            if ($fix) {
                $this->setEnv($envPath, ['APP_ENV' => 'local', 'APP_DEBUG' => 'true']);
                $this->fixed('Set APP_ENV=local and APP_DEBUG=true (shows real error messages on your laptop).');
            } else {
                $this->warnLine('APP_ENV is "'.($env['APP_ENV'] ?? 'missing').'"', 'For your laptop set APP_ENV=local and APP_DEBUG=true. (Use production only on a live server.)');
            }
        } else {
            $this->ok('APP_ENV=local');
        }

        // 4. Application key
        if (empty($env['APP_KEY'])) {
            if ($fix) {
                $this->setEnv($envPath, ['APP_KEY' => 'base64:'.base64_encode(random_bytes(32))]);
                $this->fixed('Generated APP_KEY.');
            } else {
                $this->bad('APP_KEY', 'Missing – every page shows "500 Server Error". Run: php artisan key:generate');
            }
        } else {
            $this->ok('APP_KEY is set');
        }

        // 5. Database
        $env = Dotenv::parse((string) file_get_contents($envPath));
        $driver = $env['DB_CONNECTION'] ?? 'sqlite';
        $this->checkDatabase($driver, $env, $fix);

        // 6. Writable folders
        foreach (['storage', 'storage/logs', 'storage/framework/views', 'storage/framework/sessions', 'storage/framework/cache', 'bootstrap/cache', 'public/uploads'] as $dir) {
            $path = $base.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $dir);
            if (! is_dir($path) && $fix) {
                @mkdir($path, 0775, true);
            }
            $this->check(is_dir($path) && is_writable($path), "Folder writable: {$dir}", "Make sure the folder {$dir} exists and is not read-only.");
        }

        return $this->finish();
    }

    private function checkDatabase(string $driver, array $env, bool $fix): void
    {
        try {
            if ($driver === 'sqlite') {
                $file = $env['DB_DATABASE'] ?? database_path('database.sqlite');
                if (! file_exists($file) && $fix) {
                    touch($file);
                    $this->fixed("Created SQLite database file {$file}");
                }
                $pdo = new PDO('sqlite:'.$file);
                $this->ok("Database: SQLite ({$file})");
                $this->warnLine('Using SQLite', 'Your proposal uses MySQL. To use XAMPP MySQL set DB_CONNECTION=mysql and DB_DATABASE=pearl360 in .env.');
            } else {
                $host = $env['DB_HOST'] ?? '127.0.0.1';
                $port = $env['DB_PORT'] ?? '3306';
                $name = $env['DB_DATABASE'] ?? 'pearl360';
                $user = $env['DB_USERNAME'] ?? 'root';
                $pass = $env['DB_PASSWORD'] ?? '';

                if (! extension_loaded('pdo_mysql')) {
                    $this->bad('PHP extension: pdo_mysql', 'Enable extension=pdo_mysql in php.ini.');

                    return;
                }
                $server = new PDO("mysql:host={$host};port={$port}", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]);
                $this->ok("MySQL server reachable at {$host}:{$port}");

                $exists = $server->query('SHOW DATABASES LIKE '.$server->quote($name))->fetchColumn();
                if (! $exists) {
                    if ($fix) {
                        $server->exec('CREATE DATABASE `'.str_replace('`', '', $name).'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
                        $this->fixed("Created MySQL database '{$name}'.");
                    } else {
                        $this->bad("MySQL database '{$name}'", "Does not exist. Create it in phpMyAdmin, or run with --fix.");

                        return;
                    }
                } else {
                    $this->ok("MySQL database '{$name}' exists");
                }
                $pdo = new PDO("mysql:host={$host};port={$port};dbname={$name}", $user, $pass);
            }

            $hasTables = (bool) $pdo->query($driver === 'sqlite'
                ? "SELECT name FROM sqlite_master WHERE type='table' AND name='users'"
                : "SHOW TABLES LIKE 'users'")->fetchColumn();
            if ($hasTables) {
                $admins = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
                $admins > 0
                    ? $this->ok("Tables exist ({$admins} admin account(s))")
                    : $this->warnLine('No admin account yet', 'Run: php artisan db:seed   or   php artisan pearl360:make-admin you@example.com');
            } else {
                $this->warnLine('Database tables not created yet', 'Run: php artisan migrate --seed   (setup.bat does this for you)');
            }
        } catch (PDOException $e) {
            $hint = str_contains($e->getMessage(), 'refused') || str_contains($e->getMessage(), '2002')
                ? 'MySQL is not running. Start MySQL in the XAMPP Control Panel.'
                : 'Check DB_HOST, DB_PORT, DB_USERNAME and DB_PASSWORD in .env (XAMPP default: root with an empty password).';
            $this->bad('Database connection', $hint.' ('.$e->getMessage().')');
        }
    }

    private function setEnv(string $path, array $values): void
    {
        $content = (string) file_get_contents($path);
        foreach ($values as $key => $value) {
            $line = "{$key}={$value}";
            $content = preg_match("/^{$key}=.*$/m", $content)
                ? preg_replace("/^{$key}=.*$/m", str_replace('$', '\$', $line), $content)
                : rtrim($content)."\n{$line}\n";
        }
        file_put_contents($path, $content);
    }

    private function check(bool $ok, string $label, string $help): void
    {
        $ok ? $this->ok($label) : $this->bad($label, $help);
    }

    private function ok(string $label): void
    {
        $this->line("  <fg=green>✔</> {$label}");
    }

    private function fixed(string $label): void
    {
        $this->line("  <fg=cyan>➜</> {$label}");
    }

    private function warnLine(string $label, string $help): void
    {
        $this->line("  <fg=yellow>!</> {$label}");
        $this->line("      <fg=gray>{$help}</>");
    }

    private function bad(string $label, string $help): void
    {
        $this->problems++;
        $this->line("  <fg=red>✘</> {$label}");
        $this->line("      <fg=gray>{$help}</>");
    }

    private function finish(): int
    {
        $this->newLine();
        if ($this->problems) {
            $this->error(" {$this->problems} problem(s) found. Fix them (or run: php artisan pearl360:doctor --fix) and run this check again. ");

            return self::FAILURE;
        }
        $this->info(' All good! Start the site with: php artisan serve  →  http://localhost:8000 ');

        return self::SUCCESS;
    }
}
