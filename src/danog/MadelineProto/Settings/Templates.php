<?php

namespace danog\MadelineProto\Settings;

/**
 * Web and CLI template settings for login.
 */
class Templates
{
    /**
     * Web template used for querying app information.
     */
    protected string $apiHtmlTemplate = '<!DOCTYPE html><html><head><title>MadelineProto</title></head><body><h1>MadelineProto</h1><p>%s</p><form method="POST">%s<button type="submit"/>Go</button></form></body></html>';
    /**
     * Prompt user to choose manual or automatic API ID generation.
     */
    protected string $apiChooseManualAuto = 'You did not define a valid API ID/API hash. Do you want to define it now manually, or automatically? (m/a)';
    /**
     * Settings tip for API ID generation.
     */
    protected string $apiChooseManualAutoTip = 'Note that you can also provide the API parameters directly in the code using the settings: %s';
    /**
     * Final prompt to choose mode.
     */
    protected string $apiChoosePrompt = 'Your choice (m/a): ';
    /**
     * Instructions for manual API ID generation.
     *
     * @var array{0: string, 1: string, 2: array{0: string, 1: string, 2: string, 3: string, 4: string}, 3: string}
     */
    protected array $apiManualInstructions = [
        'Login to my.telegram.org',
        'Go to API development tools',
        [
            'App title: your app\'s name, can be anything',
            'Short name: your app\'s short name, can be anything',
            'URL: your app/website\'s URL, or t.me/yourusername',
            'Platform: anything',
            'Description: Describe your app here',
        ],
        'Click on create application'
    ];
    /**
     * Manual API ID/hash prompts.
     */
    protected array $apiManualPrompts = [
        'Enter your API ID: ',
        'Enter your API hash: '
    ];
    /**
     * Auto API ID/hash prompts.
     */
    protected array $apiAutoPrompts = [
        'Enter a phone number that is already registered on Telegram: ',
        'Enter the verification code you received in Telegram: ',
        'Enter the app\'s name, can be anything: ',
        'Enter the app\'s short name, can be anything: ',
        'Enter the app/website\'s URL, or t.me/yourusername: ',
        'Describe your app: ',
    ];
}
