<?php declare(strict_types=1);

namespace danog\MadelineProto;

if (\defined('MADELINE_PHP')) {
    throw new \Exception('Please do not include madeline.php twice, use require_once \'madeline.php\';!');
}

if (!\defined('MADELINE_ALLOW_COMPOSER') && \class_exists(\Composer\Autoload\ClassLoader::class)) {
    throw new \Exception('Composer autoloader detected: madeline.php is incompatible with Composer, please install MadelineProto using composer: https://docs.madelineproto.xyz/docs/INSTALLATION.html#composer-from-existing-project');
}

\define('MADELINE_PHP', __FILE__);

class Installer
{
    const RELEASE_TEMPLATE = 'https://phar.madelineproto.xyz/release%s?v=new';
    const PHAR_TEMPLATE = 'https://github.com/danog/MadelineProto/releases/latest/download/madeline%s.phar?v=%s';

    /**
     * Phar lock instance.
     *
     * @var resource|null
     */
    private static $lock = null;
    /**
     * Installer lock instance.
     *
     * @var resource|null
     */
    private $lockInstaller = null;
    /**
     * PHP version.
     *
     * @var string
     */
    private $version;
    /**
     * Constructor.
     */
    public function __construct()
    {
        if ((PHP_MAJOR_VERSION === 8 && PHP_MINOR_VERSION < 1) || PHP_MAJOR_VERSION <= 7) {
            die('MadelineProto requires at least PHP 8.1.'.PHP_EOL);
        }
        if (PHP_INT_SIZE < 8) {
            die('A 64-bit build of PHP is required to run MadelineProto, PHP 8.1 is required.'.PHP_EOL);
        }
        $backtrace = \debug_backtrace(0);
        if (\count($backtrace) === 1) {
            if (isset($GLOBALS['argv']) && !empty($GLOBALS['argv'])) {
                $arguments = \array_slice($GLOBALS['argv'], 1);
            } elseif (isset($_GET['argv']) && !empty($_GET['argv'])) {
                $arguments = $_GET['argv'];
            } else {
                $arguments = [];
            }
            if (\count($arguments) >= 2) {
                \define(\MADELINE_WORKER_TYPE::class, \array_shift($arguments));
                \define(\MADELINE_WORKER_ARGS::class, $arguments);
            } else {
                die('MadelineProto loader: you must include this file in another PHP script, see https://docs.madelineproto.xyz for more info.'.PHP_EOL);
            }
            \define('MADELINE_REAL_ROOT', \dirname($backtrace[0]["file"]));
        }
        $this->version = (string) \min(81, (int) (PHP_MAJOR_VERSION.PHP_MINOR_VERSION));
        \define('MADELINE_PHAR_GLOB', \getcwd().DIRECTORY_SEPARATOR."madeline*-{$this->version}.phar");
        \define('MADELINE_PHAR_VERSION', \getcwd().DIRECTORY_SEPARATOR."madeline.version");
        \define('MADELINE_RELEASE_URL', \sprintf(self::RELEASE_TEMPLATE, $this->version));
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        if ($this->lockInstaller) {
            \flock($this->lockInstaller, LOCK_UN);
            \fclose($this->lockInstaller);
            $this->lockInstaller = null;
        }
    }

    /**
     * Extract composer package versions from phar.
     *
     * @return array<string, string>
     */
    private static function extractVersions(?string $release): array
    {
        $release ??= '';
        $phar = "madeline-$release.phar";
        $packages = ['danog/madelineproto' => 'old'];
        if (!\file_exists("phar://$phar/vendor/composer/installed.json")) {
            return $packages;
        }
        $composer = \json_decode(\file_get_contents("phar://$phar/vendor/composer/installed.json"), true) ?: [];
        if (!isset($composer['packages'])) {
            return $packages;
        }
        foreach ($composer['packages'] as $dep) {
            $name = $dep['name'];
            if (\strpos($name, 'phabel/transpiler') === 0) {
                $name = \explode('/', $name, 3)[2];
            }
            $version = $dep['version_normalized'];
            if ($name === 'danog/madelineproto' && \substr($version, 0, 2) === '90') {
                $version = \substr($version, 2);
            }
            $packages[$name] = $version;
        }
        return $packages;
    }

    /**
     * Report installs to composer.
     */
    private static function reportComposer(?string $local_release, ?string $remote_release): void
    {
        $previous = self::extractVersions($local_release);
        $current = self::extractVersions($remote_release);
        $postData = ['downloads' => []];
        foreach ($current as $name => $version) {
            if (isset($previous[$name]) && $previous[$name] === $version) {
                continue;
            }
            $postData['downloads'][] = [
                'name' => $name,
                'version' => $version
            ];
        }

        $phpVersion = 'PHP '.PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'.'.PHP_RELEASE_VERSION;
        $opts = ['http' =>
            [
                'method' => 'POST',
                'header' => [
                    'Content-Type: application/json',
                    \sprintf(
                        'User-Agent: Composer/%s (%s; %s; %s; %s%s)',
                        'MProto v7',
                        \function_exists('php_uname') ? @\php_uname('s') : 'Unknown',
                        \function_exists('php_uname') ? @\php_uname('r') : 'Unknown',
                        $phpVersion,
                        'streams',
                        \getenv('CI') ? '; CI' : ''
                    )
                ],
                'content' => \json_encode($postData),
                'timeout' => 6,
            ],
        ];
        @\file_get_contents("https://packagist.org/downloads/", false, \stream_context_create($opts));
    }

    /**
     * Load phar file.
     */
    private static function load(?string $release): mixed
    {
        if ($release === null) {
            if ((PHP_MAJOR_VERSION === 8 && PHP_MINOR_VERSION < 1) || PHP_MAJOR_VERSION <= 7) {
                throw new \Exception('MadelineProto requires at least PHP 8.1.');
            }
            throw new \Exception('Could not download MadelineProto, please check your internet connection and PHP configuration!');
        }
        $phar = "madeline-$release.phar";
        if (!self::$lock) {
            self::$lock = \fopen("$phar.lock", 'c');
        }
        \flock(self::$lock, LOCK_SH);
        return require_once $phar;
    }

    /**
     * Unlock phar.
     *
     */
    public static function unlock(): void
    {
        \flock(self::$lock, LOCK_UN);
    }

    /**
     * Lock installer.
     */
    private function lock(string $version): bool
    {
        if ($this->lockInstaller) {
            return true;
        }
        $this->lockInstaller = \fopen($version, 'w');
        return \flock($this->lockInstaller, LOCK_EX|LOCK_NB);
    }

    /**
     * Install MadelineProto.
     */
    public function install()
    {
        if (\file_exists(MADELINE_PHAR_VERSION)) {
            $local_release = \file_get_contents(MADELINE_PHAR_VERSION) ?: null;
        } else {
            \touch(MADELINE_PHAR_VERSION);
            $local_release = null;
        }
        \define('HAD_MADELINE_PHAR', !!$local_release);

        if ($local_release !== null && \file_exists("madeline-$local_release.phar")) {
            return self::load($local_release);
        }

        $remote_release = \file_get_contents(MADELINE_RELEASE_URL) ?: null;
        $madeline_phar = "madeline-$remote_release.phar";

        if (!$this->lock(MADELINE_PHAR_VERSION)) {
            \flock($this->lockInstaller, LOCK_EX);
            return $this->install();
        }

        if (!\file_exists($madeline_phar)) {
            for ($x = 0; $x < 10; $x++) {
                $pharTest = \file_get_contents(\sprintf(self::PHAR_TEMPLATE, $this->version, $remote_release.$x));
                if ($pharTest && \strpos($pharTest, $remote_release) !== false) {
                    $phar = $pharTest;
                    unset($pharTest);
                    break;
                }
                \sleep(1);
            }
            if (!isset($phar)) {
                return self::load($local_release);
            }

            self::$lock = \fopen("$madeline_phar.lock", 'w');
            \flock(self::$lock, LOCK_EX);
            \file_put_contents($madeline_phar, $phar);
            unset($phar);

            self::reportComposer($local_release, $remote_release);
        }
        \fwrite($this->lockInstaller, $remote_release);
        \fflush($this->lockInstaller);
        return self::load($remote_release);
    }
}

return (new \danog\MadelineProto\Installer)->install();
