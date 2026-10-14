<?php

namespace Tests\Unit;

use App\Services\ActivityClient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ActivityClientTest extends TestCase
{
    #[DataProvider('clients')]
    public function test_describes_browser_os_and_device(?string $agent, ?string $hint, array $expected): void
    {
        $this->assertSame($expected, ActivityClient::describe($agent, $hint));
    }

    public static function clients(): array
    {
        $windows = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/140.0.0.0 Safari/537.36';

        return [
            'Windows 11 Edge' => [$windows . ' Edg/140.0.0.0', '19.0.0', ['device' => 'Desktop/Laptop', 'os' => 'Windows 11', 'browser' => 'Microsoft Edge']],
            'Windows 10 Chrome' => [$windows, '10.0.0', ['device' => 'Desktop/Laptop', 'os' => 'Windows 10', 'browser' => 'Google Chrome']],
            'Windows without hints' => [$windows, null, ['device' => 'Desktop/Laptop', 'os' => 'Windows 10 / 11', 'browser' => 'Google Chrome']],
            'invalid hint' => [$windows, 'Windows 11', ['device' => 'Desktop/Laptop', 'os' => 'Windows 10 / 11', 'browser' => 'Google Chrome']],
            'Windows 7 Firefox' => ['Mozilla/5.0 (Windows NT 6.1) Gecko/20100101 Firefox/115.0', '0.0.0', ['device' => 'Desktop/Laptop', 'os' => 'Windows 7', 'browser' => 'Mozilla Firefox']],
            'Android phone' => ['Mozilla/5.0 (Linux; Android 10; K) Chrome/140.0.0.0 Mobile Safari/537.36', null, ['device' => 'Mobile', 'os' => 'Android', 'browser' => 'Google Chrome']],
            'Android tablet' => ['Mozilla/5.0 (Linux; Android 10; K) Chrome/140.0.0.0 Safari/537.36', null, ['device' => 'Tablet', 'os' => 'Android', 'browser' => 'Google Chrome']],
            'iPad with Mobile token' => ['Mozilla/5.0 (iPad; CPU OS 18_0 like Mac OS X) Version/18.0 Mobile/15E148 Safari/604.1', null, ['device' => 'Tablet', 'os' => 'iPadOS', 'browser' => 'Safari']],
            'iPhone Chrome' => ['Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) CriOS/140.0.0.0 Mobile/15E148 Safari/604.1', null, ['device' => 'Mobile', 'os' => 'iOS', 'browser' => 'Google Chrome']],
            'macOS Safari' => ['Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) Version/18.0 Safari/605.1.15', null, ['device' => 'Desktop/Laptop', 'os' => 'macOS', 'browser' => 'Safari']],
            'ChromeOS' => ['Mozilla/5.0 (X11; CrOS x86_64 14541.0.0) Chrome/140.0.0.0 Safari/537.36', null, ['device' => 'Desktop/Laptop', 'os' => 'ChromeOS', 'browser' => 'Google Chrome']],
            'Opera before Chrome' => [$windows . ' OPR/122.0.0.0', null, ['device' => 'Desktop/Laptop', 'os' => 'Windows 10 / 11', 'browser' => 'Opera']],
            'unknown agent' => ['custom-client/1.0', null, ['device' => 'Unknown device', 'os' => 'Unknown OS', 'browser' => 'Unknown browser']],
            'legacy record' => [null, null, ['device' => 'Not recorded', 'os' => 'Not recorded', 'browser' => 'Not recorded']],
        ];
    }
}
