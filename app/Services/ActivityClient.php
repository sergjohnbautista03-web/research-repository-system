<?php

namespace App\Services;

class ActivityClient
{
    public static function describe(?string $userAgent, ?string $platformVersion = null): array
    {
        $ua = trim($userAgent ?? '');
        if ($ua === '') {
            return ['device' => 'Not recorded', 'os' => 'Not recorded', 'browser' => 'Not recorded'];
        }

        $browser = match (true) {
            preg_match('/Edg(?:e|A|iOS)?\//i', $ua) === 1 => 'Microsoft Edge',
            preg_match('/(?:OPR|Opera|OPiOS)\//i', $ua) === 1 => 'Opera',
            stripos($ua, 'SamsungBrowser/') !== false => 'Samsung Internet',
            preg_match('/(?:Chrome|CriOS)\//i', $ua) === 1 => 'Google Chrome',
            preg_match('/(?:Firefox|FxiOS)\//i', $ua) === 1 => 'Mozilla Firefox',
            stripos($ua, 'Safari/') !== false => 'Safari',
            default => 'Unknown browser',
        };

        $os = 'Unknown OS';
        if (preg_match('/Windows NT ([\d.]+)/i', $ua, $windows)) {
            $os = match ($windows[1]) {
                // Both versions report NT 10.0; client hints can distinguish them.
                '10.0' => 'Windows 10 / 11',
                '6.3' => 'Windows 8.1',
                '6.2' => 'Windows 8',
                '6.1' => 'Windows 7',
                default => 'Windows',
            };
            if ($windows[1] === '10.0' && preg_match('/^\d+\.\d+\.\d+$/D', $platformVersion ?? '')) {
                $major = (int) explode('.', $platformVersion)[0];
                if ($major >= 13) $os = 'Windows 11';
                elseif ($major > 0) $os = 'Windows 10';
            }
        } elseif (stripos($ua, 'Android') !== false) {
            $os = 'Android';
        } elseif (stripos($ua, 'iPad') !== false) {
            $os = 'iPadOS';
        } elseif (preg_match('/iPhone|iPod/i', $ua)) {
            $os = 'iOS';
        } elseif (stripos($ua, 'CrOS') !== false) {
            $os = 'ChromeOS';
        } elseif (stripos($ua, 'Macintosh') !== false || stripos($ua, 'Mac OS X') !== false) {
            $os = 'macOS';
        } elseif (stripos($ua, 'Linux') !== false) {
            $os = 'Linux';
        }

        $device = match (true) {
            preg_match('/iPad|Tablet|Silk\//i', $ua) === 1 => 'Tablet',
            stripos($ua, 'Android') !== false && stripos($ua, 'Mobile') === false => 'Tablet',
            preg_match('/Mobile|iPhone|iPod/i', $ua) === 1 => 'Mobile',
            $os !== 'Unknown OS' => 'Desktop/Laptop',
            default => 'Unknown device',
        };

        return compact('device', 'os', 'browser');
    }
}
