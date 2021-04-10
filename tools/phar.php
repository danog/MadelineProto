<?php

\define('MADELINE_PHP', __FILE__);

function ___install_madeline()
{
    if (\count(\debug_backtrace(0)) === 1) {
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
    }
    $old = false;
    if (PHP_MAJOR_VERSION === 5) {
        if (PHP_MINOR_VERSION < 6) {
            throw new \Exception('MadelineProto requires at least PHP 7.1 to run');
        }
        $old = true;
    }
    if (PHP_MAJOR_VERSION === 7 && PHP_MINOR_VERSION === 0) {
        $old = true;
    }
    if ($old) {
        $newline = PHP_EOL;
        if (PHP_SAPI !== 'cli') {
            $newline = '<br>'.$newline;
        }
        echo "**********************************************************************************$newline";
        echo "**********************************************************************************$newline$newline";
        echo "YOU ARE USING AN OLD AND BUGGED VERSION OF PHP, PLEASE UPDATE TO PHP 8.0$newline";
        echo "PHP 5/7.0 USERS WILL NOT RECEIVE PHP UPDATES AND BUGFIXES: https://www.php.net/eol.php$newline";
        echo "PHP 5/7.0 USERS WILL NOT RECEIVE MADELINEPROTO UPDATES AND BUGFIXES$newline$newline";
        echo "SUPPORTED VERSIONS: PHP 7.1, 7.2, 7.3, 7.4, 8.0+$newline";
        echo "RECOMMENDED VERSION: PHP 8.0$newline$newline";
        echo "**********************************************************************************$newline";
        echo "**********************************************************************************$newline";
        unset($newline);
    }

    // MTProxy update
    $file = \debug_backtrace(0, 1)[0]['file'];
    if (\file_exists($file)) {
        $contents = \file_get_contents($file);

        if (\strpos($contents, 'new \danog\MadelineProto\Server') && \in_array($contents, [@\file_get_contents('https://github.com/danog/MadelineProtoPhar/raw/2270bd9a94d168a5e6731ffd7e61821ea244beff/mtproxyd'), @\file_get_contents('https://github.com/danog/MadelineProtoPhar/raw/7cabb718ec3ccb79e3c8e3d34f5bccbe3f63b0fd/mtproxyd')]) && ($mtproxyd = @\file_get_contents('https://phar.madelineproto.xyz/mtproxyd?v=new'))) {
            \file_put_contents($file, $mtproxyd, LOCK_EX);

            return;
        }
    }

    // Template strings for madelineProto update URLs
    $release_template = 'https://phar.madelineproto.xyz/release%s?v=new';
    $phar_template = 'https://phar.madelineproto.xyz/madeline%s.phar?v=new';

    // Version definition
    $custom_branch = \defined('MADELINE_BRANCH') ? MADELINE_BRANCH : null;
    if ($custom_branch === '') { // If the constant is an empty string, default to the latest alpha build
        $custom_branch = 'master';
    } elseif ($custom_branch === null) { // If the constant is not defined, default to the latest stable build
        $custom_branch = '';
    }

    $version = (string) \min(80, (int) (PHP_MAJOR_VERSION.PHP_MINOR_VERSION));
    if ($version === "56") {
        $version = 5;
    }
    $versions = [];
    if ($custom_branch !== '') {
        $versions []= "$version-$custom_branch";
        $versions []= "70-$custom_branch";
    }
    $versions []= $version;
    $versions []= 70;

    // Checking if defined branch/default branch builds can be downloaded
    foreach ($versions as $chosen) {
        if ($release = @\file_get_contents(\sprintf($release_template, $chosen))) {
            break;
        }
    }
    if (!$release) {
        return;
    }

    $madeline_phar = "madeline-$version.phar";
    \define('HAD_MADELINE_PHAR', \file_exists($madeline_phar));

    if (!\file_exists($madeline_phar) || !\file_exists("$madeline_phar.version") || \file_get_contents("$madeline_phar.version") !== $release) {
        $phar = \file_get_contents(\sprintf($phar_template, $chosen));

        if ($phar) {
            $extractVersions = static function ($ext = '') use ($madeline_phar) {
                if (!\file_exists("phar://$madeline_phar$ext/vendor/composer/installed.json")) {
                    return [];
                }
                $composer = \json_decode(\file_get_contents("phar://$madeline_phar$ext/vendor/composer/installed.json"), true) ?: [];
                if (!isset($composer['packages'])) {
                    return [];
                }
                $packages = [];
                foreach ($composer['packages'] as $dep) {
                    $packages[$dep['name']] = $dep['version_normalized'];
                }
                return $packages;
            };
            $previous = [];
            if (\file_exists($madeline_phar)) {
                \copy($madeline_phar, "$madeline_phar.old");
                $previous = $extractVersions('.old');
                \unlink("$madeline_phar.old");
            }
            $previous['danog/madelineproto'] = 'old';

            \file_put_contents($madeline_phar, $phar, LOCK_EX);
            \file_put_contents("$madeline_phar.version", $release, LOCK_EX);

            $current = $extractVersions();
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

            if (\defined('HHVM_VERSION')) {
                $phpVersion = 'HHVM '.HHVM_VERSION;
            } else {
                $phpVersion = 'PHP '.PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'.'.PHP_RELEASE_VERSION;
            }
            $opts = ['http' =>
                [
                    'method' => 'POST',
                    'header' => [
                        'Content-Type: application/json',
                        \sprintf(
                            'User-Agent: Composer/%s (%s; %s; %s; %s%s)',
                            'MadelineProto',
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
    }

    return $madeline_phar;
}

return require_once ___install_madeline();
