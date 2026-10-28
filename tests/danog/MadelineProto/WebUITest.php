<?php

declare(strict_types=1);

/**
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Test;

use AssertionError;
use danog\MadelineProto\Lang;
use danog\MadelineProto\MyTelegramOrgWrapper;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\Templates;
use danog\MadelineProto\WebUI\Action;
use danog\MadelineProto\WebUI\Alert;
use danog\MadelineProto\WebUI\Field;
use danog\MadelineProto\WebUI\FieldType;
use danog\MadelineProto\WebUI\Page;
use danog\MadelineProto\WebUI\SafeHtml;
use danog\MadelineProto\WebUI\Step;
use danog\MadelineProto\WebUI\Template;
use danog\MadelineProto\WebUI\TemplateEngine;
use danog\MadelineProto\WebUI\WebUI;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/** @internal */
final class WebUITest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        Lang::$current_lang = Lang::$lang['en'];
        $this->dir = sys_get_temp_dir().'/madeline-webui-'.bin2hex(random_bytes(6));
        mkdir($this->dir.'/partials', 0o777, true);
    }

    protected function tearDown(): void
    {
        foreach (['partials/alerts.phtml', 'page.phtml', 'child.phtml', 'base.phtml', 'assets/tweb/app.js', 'assets/secret.txt', 'assets/link.js', 'secret.js'] as $file) {
            if (is_link("$this->dir/$file")) {
                unlink("$this->dir/$file");
                continue;
            }
            if (file_exists("$this->dir/$file")) {
                unlink("$this->dir/$file");
            }
        }
        foreach (['partials', 'assets/tweb', 'assets'] as $dir) {
            if (is_dir("$this->dir/$dir")) {
                rmdir("$this->dir/$dir");
            }
        }
        rmdir($this->dir);
        unset($_GET[WebUI::ASSET_PARAMETER], $_SERVER['HTTP_X_MADELINEPROTO_WEBUI'], $_SERVER['HTTP_IF_NONE_MATCH']);
    }

    private static function loginPage(): Page
    {
        return new Page(
            title: 'Log in',
            lead: 'Lead <b>text</b>',
            step: Step::ACCOUNT,
            fields: [
                new Field('type', 'Log in as', FieldType::CHOICE, 'phone', options: ['phone' => 'User', 'bot' => 'Bot']),
                new Field('phone_number', 'Phone', FieldType::PHONE, '"><script>alert(1)</script>', showWhen: ['type', 'phone']),
                new Field('password', 'Password', FieldType::PASSWORD, hint: 'Hint: <pony>'),
            ],
            submit: 'Continue',
            alerts: [Alert::error('Error <i>x</i>'), Alert::info(SafeHtml::linkify('See https://my.telegram.org.'))],
            actions: [new Action('Manual', 'api_setup', 'manual')],
            qrEndpoint: '/login.php',
        );
    }

    public function testPageEscapesTextAndKeepsTrustedHtml(): void
    {
        $html = WebUI::render(new Templates, self::loginPage());
        self::assertStringNotContainsString('<script>alert(1)</script>', $html);
        self::assertStringContainsString('value="&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;"', $html);
        self::assertStringContainsString('Lead &lt;b&gt;text&lt;/b&gt;', $html);
        self::assertStringContainsString('Hint: &lt;pony&gt;', $html);
        self::assertStringContainsString('Error &lt;i&gt;x&lt;/i&gt;', $html);
        // The final dot is not part of the link
        self::assertStringContainsString('<a href="https://my.telegram.org" target="_blank" rel="noopener noreferrer">https://my.telegram.org</a>.', $html);
    }

    public function testPageRendersTheFormAndTheState(): void
    {
        $html = WebUI::render($this->withApp(), self::loginPage());
        self::assertStringContainsString('<form method="post" class="form" data-madeline-form>', $html);
        self::assertStringContainsString('data-show-when="type=phone"', $html);
        // Without JavaScript all the conditional fields are shown: they can't be required
        self::assertSame(1, preg_match('~<input type="tel" id="f-phone_number"[^>]*>~', $html, $input));
        self::assertStringNotContainsString(' required', $input[0]);
        self::assertSame(1, preg_match('~<input type="password"[^>]*>~', $html, $input));
        self::assertStringContainsString(' required', $input[0]);
        self::assertStringContainsString('name="api_setup" value="manual"', $html);
        self::assertStringContainsString('aria-current="step"', $html);

        self::assertSame(1, preg_match('~<script type="application/json" id="madeline-state">(.*?)</script>~s', $html, $matches));
        // The state can't close its script tag
        self::assertStringNotContainsString('<', $matches[1]);
        $state = json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);
        self::assertSame('Log in', $state['page']['title']);
        self::assertSame('account', $state['page']['step']);
        self::assertSame('"><script>alert(1)</script>', $state['page']['fields'][1]['value']);
        self::assertSame(['type', 'phone'], $state['page']['fields'][1]['showWhen']);
        self::assertSame(['html' => '<a href="https://my.telegram.org" target="_blank" rel="noopener noreferrer">https://my.telegram.org</a>.'], ['html' => substr($state['page']['alerts'][1]['message']['html'], 4)]);
        self::assertSame(['app', 'account', 'verify', 'done'], array_column($state['steps'], 'id'));
        self::assertSame('Show', $state['strings']['webShowPassword']);
        // The strings of the web app, without their prefix in langs/*.json, in the language of the session
        self::assertSame('Sign in to MadelineProto', $state['strings']['Login.Title']);
        self::assertArrayNotHasKey('tweb.Login.Title', $state['strings']);
        $english = Lang::$current_lang;
        try {
            Lang::$current_lang = ['tweb.Login.Title' => 'Accedi a MadelineProto'] + $english;
            self::assertSame('Accedi a MadelineProto', WebUI::getStateArray(self::loginPage())['strings']['Login.Title']);
        } finally {
            Lang::$current_lang = $english;
        }
        self::assertSame('en', $state['lang']);
    }

    public function testInlinedScriptsCantCloseTheirTag(): void
    {
        self::assertSame('a<\/script><\!--b', Template::escapeScript('a</SCRIPT><!--b'));
    }

    /**
     * A template directory with a web app bundle.
     */
    private function withApp(): Templates
    {
        mkdir("$this->dir/assets/tweb", 0o777, true);
        file_put_contents("$this->dir/assets/tweb/app.js", 'console.log(1)');
        return (new Templates)->addDirectory($this->dir);
    }

    public function testWithTheAppTheMarkupIsOnlyForBrowsersWithoutJavaScript(): void
    {
        $html = WebUI::render($this->withApp(), self::loginPage());
        self::assertStringContainsString('<link id="madeline-app-css" rel="stylesheet" media="print" href="/?madelineAsset=assets/tweb/app.css">', $html);
        self::assertStringContainsString('<div id="madeline-app"></div>', $html);
        self::assertStringContainsString('<script defer src="/?madelineAsset=assets/tweb/app.js"></script>', $html);
        // The markup and its stylesheet are in <noscript>
        self::assertSame(1, preg_match('~<body>.*<noscript>(.*)</noscript>~s', $html, $noscript));
        self::assertStringContainsString('<form method="post" class="form" data-madeline-form>', $noscript[1]);
        self::assertSame(1, preg_match('~<noscript><style>~', $html));
    }

    public function testWithoutTheAppThereIsJustTheMarkup(): void
    {
        $templates = (new Templates)->addDirectory($this->dir);
        if ($templates->getEngine()->exists('assets/tweb/app.js')) {
            // The builtin bundle: render with only the custom directory's templates, through a fake engine
            $html = (new TemplateEngine([$this->dir]))->render('page', ['page' => self::loginPage(), 'state' => '{}']);
        } else {
            $html = WebUI::render($templates, self::loginPage());
        }
        self::assertStringNotContainsString('<noscript>', $html);
        self::assertStringContainsString('<form method="post" class="form" data-madeline-form>', $html);
    }

    public function testJsonRequestsGetTheState(): void
    {
        $_SERVER['HTTP_X_MADELINEPROTO_WEBUI'] = 'json';
        self::assertFalse(WebUI::redirectToSelf());
        $result = json_decode(self::capture(static fn () => WebUI::write(new Templates, self::loginPage())), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame('Log in', $result['state']['page']['title']);
        self::assertSame('/?madelineAsset=', $result['state']['assetUrl']);
        self::assertSame(['loggedIn' => true], json_decode(self::capture(static fn () => self::assertTrue(WebUI::writeLoggedIn())), true));

        unset($_SERVER['HTTP_X_MADELINEPROTO_WEBUI']);
        self::assertFalse(WebUI::writeLoggedIn());
    }

    public function testAssetsAreServedFromTheTemplateDirectories(): void
    {
        $templates = $this->withApp();
        self::assertFalse(WebUI::serveAsset($templates));

        $_GET[WebUI::ASSET_PARAMETER] = 'assets/tweb/app.js';
        self::assertSame('console.log(1)', self::capture(static fn () => self::assertTrue(WebUI::serveAsset($templates))));
        $_GET[WebUI::ASSET_PARAMETER] = 'assets/mark.svg';
        self::assertStringStartsWith('<svg', self::capture(static fn () => WebUI::serveAsset($templates)));

        // Only assets, of known types, inside the template directories
        file_put_contents("$this->dir/assets/secret.txt", 'secret');
        file_put_contents("$this->dir/secret.js", 'secret');
        // A symlink out of the assets
        symlink("$this->dir/secret.js", "$this->dir/assets/link.js");
        foreach (['page.phtml', 'assets/secret.txt', 'secret.js', 'assets/../secret.js', 'assets/../../../../etc/passwd', '/etc/passwd', 'assets/missing.js', 'assets/link.js'] as $path) {
            $_GET[WebUI::ASSET_PARAMETER] = $path;
            self::assertSame('Not found', self::capture(static fn () => self::assertTrue(WebUI::serveAsset($templates))), $path);
        }
    }

    private static function capture(callable $callback): string
    {
        ob_start();
        try {
            $callback();
        } finally {
            $output = ob_get_clean();
        }
        return $output;
    }

    public function testStatusPageHasNoForm(): void
    {
        $html = WebUI::render(new Templates, new Page('The bot was started!', step: Step::DONE));
        self::assertStringNotContainsString('<form', $html);
        self::assertStringContainsString('<h1>The bot was started!</h1>', $html);
    }

    public function testCustomDirectoriesOverrideSingleTemplates(): void
    {
        file_put_contents("$this->dir/partials/alerts.phtml", '<?php foreach ($alerts as $alert): ?><div class="custom-alert"><?= $this->e($alert->message) ?></div><?php endforeach ?>');
        $templates = (new Templates)->addDirectory($this->dir);
        $html = WebUI::render($templates, self::loginPage());
        self::assertStringContainsString('<div class="custom-alert">Error &lt;i&gt;x&lt;/i&gt;</div>', $html);
        // The other templates are still the builtin ones
        self::assertStringContainsString('class="shell"', $html);
        self::assertSame([$this->dir, TemplateEngine::BUILTIN_DIRECTORY], $templates->getEngine()->getDirectories());
    }

    public function testLayoutsAndSections(): void
    {
        file_put_contents("$this->dir/base.phtml", '<?php $this->start("title") ?>Default<?php $this->stop() ?><title><?= $this->section("title") ?></title><body><?= $this->section("content") ?></body>');
        file_put_contents("$this->dir/child.phtml", '<?php $this->layout("base") ?><?php $this->start("title") ?><?= $this->e($name) ?><?php $this->stop() ?><p><?= $this->e($name) ?></p>');
        $engine = new TemplateEngine([$this->dir]);
        self::assertSame('<title>a&amp;b</title><body><p>a&amp;b</p></body>', $engine->render('child', ['name' => 'a&b']));
        self::assertSame('<title>Default</title><body></body>', $engine->render('base'));
    }

    public function testFailedRendersCleanTheOutputBuffers(): void
    {
        file_put_contents("$this->dir/page.phtml", '<?php $this->start("x") ?>partial<?php throw new \RuntimeException("boom");');
        $level = ob_get_level();
        try {
            (new TemplateEngine([$this->dir]))->render('page');
            self::fail('Expected an exception');
        } catch (\RuntimeException $e) {
            self::assertSame('boom', $e->getMessage());
        }
        self::assertSame($level, ob_get_level());
    }

    public function testTemplateNamesCannotEscapeTheDirectories(): void
    {
        $this->expectException(AssertionError::class);
        (new TemplateEngine)->render('../../../../etc/passwd');
    }

    public function testTemplateSettingsSurviveSerialization(): void
    {
        $settings = new Settings;
        $settings->getTemplates()->addDirectory($this->dir);
        $settings->getTemplates()->getEngine();
        /** @var Settings */
        $settings = unserialize(serialize($settings));
        self::assertSame([$this->dir, TemplateEngine::BUILTIN_DIRECTORY], $settings->getTemplates()->getEngine()->getDirectories());

        $merged = new Settings;
        $merged->merge($settings);
        self::assertSame([$this->dir], $merged->getTemplates()->getDirectories());
    }

    public function testSelfUrlIsAlwaysSameOrigin(): void
    {
        $uri = $_SERVER['REQUEST_URI'] ?? null;
        try {
            foreach ([
                '/login.php?x=1' => '/login.php',
                '//evil.example/login.php' => '/evil.example/login.php',
                '/\\evil.example/' => '/evil.example/',
                '' => '/',
            ] as $requestUri => $expected) {
                $_SERVER['REQUEST_URI'] = $requestUri;
                self::assertSame($expected, WebUI::getSelfUrl());
            }
            // With the query of the page (which may select the session), without the parameters of the web UI
            foreach ([
                '/index.php?bot=a&getQrCode' => '/index.php?bot=a',
                '/index.php?waitQrCodeOrLogin=&x=1' => '/index.php?x=1',
                '/index.php?getQrCode' => '/index.php',
                '/index.php?madelineAsset=assets/tweb/app.js&bot=a' => '/index.php?bot=a',
                '//evil.example/x?bot=a' => '/evil.example/x?bot=a',
                '/index.php?bot.name=a%20b&getQrCode' => '/index.php?bot.name=a%20b',
            ] as $requestUri => $expected) {
                $_SERVER['REQUEST_URI'] = $requestUri;
                self::assertSame($expected, WebUI::getSelfUrl(true));
            }
        } finally {
            if ($uri === null) {
                unset($_SERVER['REQUEST_URI']);
            } else {
                $_SERVER['REQUEST_URI'] = $uri;
            }
        }
    }

    public function testTheSessionToLogInIsChosenOnTheLoginPages(): void
    {
        $uri = $_SERVER['REQUEST_URI'] ?? null;
        $_SERVER['REQUEST_URI'] = '/index.php?bot=a%20b&madelineSession=1&getQrCode';
        try {
            // A single session to log in: nothing to choose
            WebUI::setSessions(['1' => 'b.madeline'], '1');
            self::assertSame([], WebUI::addSessions(self::loginPage())->sessions);

            WebUI::setSessions(['named' => 'named', '1' => 'b.madeline', 'x"><b>' => 'x"><b>'], '1');
            $page = WebUI::addSessions(self::loginPage());
            $html = WebUI::render(new Templates, $page);
            self::assertSame(1, preg_match('~<form method="get" class="sessions" action="/index.php">(.*?)</form>~s', $html, $form));
            // The other parameters of the page are kept, as is; not the one it sets, nor those of the web UI
            self::assertStringContainsString('<input type="hidden" name="bot" value="a b">', $form[1]);
            self::assertStringNotContainsString('getQrCode', $form[1]);
            self::assertSame(1, substr_count($form[1], 'name="madelineSession"'));
            self::assertSame(3, preg_match_all('~<option~', $form[1]));
            self::assertStringContainsString('<option value="1" selected>b.madeline</option>', $form[1]);
            self::assertStringContainsString('<option value="x&quot;&gt;&lt;b&gt;">x&quot;&gt;&lt;b&gt;</option>', $form[1]);

            // In order, as a list (a JSON object would be reordered in the browser)
            $state = WebUI::getStateArray($page);
            self::assertSame([
                'param' => 'madelineSession',
                'label' => 'Session',
                'selected' => '1',
                'options' => [['key' => 'named', 'name' => 'named'], ['key' => '1', 'name' => 'b.madeline'], ['key' => 'x"><b>', 'name' => 'x"><b>']],
            ], json_decode(json_encode($state['page'], JSON_THROW_ON_ERROR), true)['sessions']);

            WebUI::setSessions([], null);
            self::assertNull(json_decode(json_encode(WebUI::addSessions(self::loginPage()), JSON_THROW_ON_ERROR), true)['sessions']);
            self::assertStringNotContainsString('class="sessions"', WebUI::render(new Templates, WebUI::addSessions(self::loginPage())));
        } finally {
            WebUI::setSessions([], null);
            if ($uri === null) {
                unset($_SERVER['REQUEST_URI']);
            } else {
                $_SERVER['REQUEST_URI'] = $uri;
            }
        }
    }

    public function testSessionsOfTheLegacyTemplatesStillLoad(): void
    {
        $deprecations = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$deprecations): bool {
            $deprecations[] = $errstr;
            return true;
        }, E_DEPRECATED | E_USER_DEPRECATED);
        try {
            // Settings saved with the removed sprintf template
            $class = Templates::class;
            $templates = unserialize('O:'.\strlen($class).':"'.$class.'":3:{'
                .'s:15:"'."\0*\0htmlTemplate".'";s:4:"<p/>";'
                .'s:14:"'."\0*\0directories".'";a:1:{i:0;s:4:"/tmp";}'
                .'s:10:"'."\0*\0changed".'";b:0;'
                .'}');
            self::assertInstanceOf(Templates::class, $templates);
            self::assertSame(['/tmp'], $templates->getDirectories());

            // A session saved with the removed API ID UI template
            $class = \danog\MadelineProto\APIWrapper::class;
            $wrapper = unserialize('O:'.\strlen($class).':"'.$class.'":2:{'
                .'s:'.(\strlen($class) + 5).':"'."\0$class\0API".'";N;'
                .'s:'.(\strlen($class) + 16).':"'."\0$class\0webApiTemplate".'";s:4:"<p/>";'
                .'}');
            self::assertInstanceOf(\danog\MadelineProto\APIWrapper::class, $wrapper);
            self::assertNull($wrapper->getAPI());
            // Saved through __sleep(), the removed private property keeps its mangled name, which no code can
            // access, nor unset: it isn't saved again
            self::assertStringNotContainsString('webApiTemplate', serialize($wrapper));
        } finally {
            restore_error_handler();
        }
        self::assertSame([], $deprecations);
    }

    public function testAlertsAsText(): void
    {
        self::assertSame(
            "Title\nVisit https://my.telegram.org & retry.",
            Alert::error(SafeHtml::linkify('Visit https://my.telegram.org & retry.'), 'Title')->toText(),
        );
    }

    public function testMyTelegramOrgPagesAreParsed(): void
    {
        $wrapper = new MyTelegramOrgWrapper(new Settings);
        $parse = new ReflectionMethod($wrapper, 'parseApps');

        $create = '<html><head><title>Create new application</title></head><body><form><input type="hidden" name="hash" value="abc123def"/></form></body></html>';
        self::assertNull($parse->invoke($wrapper, $create));
        self::assertSame('abc123def', (fn () => $this->creation_hash)->call($wrapper));

        $app = <<<'HTML'
            <html><head><title>App configuration</title></head><body>
            <div class="form-group">
              <label for="app_id" class="col-md-4 text-right control-label">App api_id:</label>
              <div class="col-md-7">
                <span class="form-control input-xlarge uneditable-input" onclick="this.select();"><strong>1234567</strong></span>
              </div>
            </div>
            <div class="form-group">
              <label for="app_hash" class="col-md-4 text-right control-label">App api_hash:</label>
              <div class="col-md-7">
                <span class="form-control input-xlarge uneditable-input" onclick="this.select();">0123456789abcdef0123456789abcdef</span>
              </div>
            </div>
            </body></html>
            HTML;
        self::assertSame(['api_id' => 1234567, 'api_hash' => '0123456789abcdef0123456789abcdef'], $parse->invoke($wrapper, $app));

        // The redesign of 2026: the hash is masked, its value in data-value; other forms have hashes and numbers
        $app = <<<'HTML'
            <html><head><title>App configuration</title></head><body>
            <form id="fcm_delete_form" method="POST" action="/apps/fcm_service_account_delete">
              <input type="hidden" name="hash" value="2cfd3bd905bad2857a"/>
            </form>
            <div class="head"><h1>My app</h1><p>Web · created 5 Apr 2025.</p></div>
            <div class="card">
              <h2>Credentials</h2>
              <p class="sub">Keep api_hash private.</p>
              <div class="creds">
                <div class="cred">
                  <div class="k">App api_id</div>
                  <div class="v"><span class="vv">7654321</span>
                    <span class="icon-btns"><button type="button" class="icon-btn" title="Copy" data-copy="7654321"><svg viewBox="0 0 24 24"><rect x="8" y="8" width="12" height="12" rx="2.5"/></svg></button></span>
                  </div>
                </div>
                <div class="cred">
                  <div class="k">App api_hash <span class="badge grey">secret</span></div>
                  <div class="v masked" id="api_hash_value" data-value="fedcba9876543210fedcba9876543210"><span class="vv">••••••••••••••••••••••••••••••••</span></div>
                </div>
              </div>
            </div>
            </body></html>
            HTML;
        self::assertSame(['api_id' => 7654321, 'api_hash' => 'fedcba9876543210fedcba9876543210'], $parse->invoke($wrapper, $app));

        // An app page without the values: logged in, but unreadable
        (fn () => $this->logged = true)->call($wrapper);
        try {
            $parse->invoke($wrapper, '<html><head><title>App configuration</title></head><body>New layout</body></html>');
            self::fail('Expected an exception');
        } catch (\danog\MadelineProto\Exception $e) {
            self::assertStringContainsString('Could not find the API ID and hash', $e->getMessage());
        }
        self::assertTrue($wrapper->loggedIn());
    }

    public function testMyTelegramOrgErrorsAreReadable(): void
    {
        $error = new ReflectionMethod(MyTelegramOrgWrapper::class, 'getError');
        self::assertSame('Sorry, too many tries. Please try again later.', $error->invoke(null, '{"text":"Sorry, too many tries. Please try again later.","field":false}'));
        self::assertSame('ERROR', $error->invoke(null, "ERROR\n"));
        self::assertSame('Invalid confirmation code!', $error->invoke(null, 'Invalid confirmation code!'));
        // What MadelineProto says itself is in the language of the session (my.telegram.org's own texts are English)
        self::assertSame('Empty response from my.telegram.org', $error->invoke(null, ''));
        Lang::$current_lang = Lang::$lang['it'];
        self::assertSame('Risposta vuota da my.telegram.org', $error->invoke(null, ''));
    }

    public function testEveryStringUsedIsInTheLanguageFiles(): void
    {
        $english = json_decode((string) file_get_contents(__DIR__.'/../../../langs/en.json'), true, flags: JSON_THROW_ON_ERROR);
        $used = [];
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(__DIR__.'/../../../src', \FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            if (!preg_match('/\.(php|phtml)$/', $file->getFilename()) || $file->getFilename() === 'Lang.php') {
                continue;
            }
            $code = (string) file_get_contents($file->getPathname());
            preg_match_all('/Lang::\$(?:current_lang|lang\[\'en\'\])\[\'([A-Za-z0-9_.]+)\'\]|->t\(\'([A-Za-z0-9_.]+)\'|Template::translate\(\'([A-Za-z0-9_.]+)\'/', $code, $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                $key = $match[1] ?: ($match[2] ?? '') ?: ($match[3] ?? '');
                $used[$key][] = basename($file->getPathname());
            }
        }
        self::assertNotEmpty($used);
        foreach ($used as $key => $where) {
            self::assertArrayHasKey($key, $english, "$key, used in ".implode(', ', array_unique($where)).", is not in langs/en.json");
        }
    }

    public function testMyTelegramOrgStateSurvivesSerialization(): void
    {
        $wrapper = new MyTelegramOrgWrapper(new Settings);
        (fn () => [$this->number, $this->hash] = ['+391234567890', 'random'])->call($wrapper);
        /** @var MyTelegramOrgWrapper */
        $wrapper = unserialize(serialize($wrapper));
        self::assertSame('+391234567890', $wrapper->getPhoneNumber());
        self::assertFalse($wrapper->loggedIn());
    }
}
