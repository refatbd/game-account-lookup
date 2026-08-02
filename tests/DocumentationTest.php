<?php

declare(strict_types=1);

namespace Refatbd\GameAccountLookup\Tests;

use PHPUnit\Framework\TestCase;
use Refatbd\GameAccountLookup\Registry\GameRegistry;

final class DocumentationTest extends TestCase
{
    public function testSupportedGameTableMatchesRegistryCount(): void
    {
        $root = dirname(__DIR__);
        $readme = (string) file_get_contents($root . '/README.md');
        $docs = (string) file_get_contents($root . '/docs/SUPPORTED_GAMES.md');
        $count = count((new GameRegistry())->list());

        self::assertStringContainsString("**{$count} game definitions**", $readme);
        self::assertStringContainsString("**{$count} game definitions**", $docs);
        self::assertStringContainsString('| Free Fire | `freefire` |', $readme);
        self::assertStringContainsString('| PUBG Mobile | `pubgmobile` |', $readme);
    }

    public function testDeveloperDocumentationAndTemplateArePresent(): void
    {
        $root = dirname(__DIR__);
        foreach ([
            'docs/DEVELOPER_GUIDE.md',
            'docs/API_REFERENCE.md',
            'docs/WEB_TESTER.md',
            'docs/TESTING.md',
            'docs/PROVIDER_AVAILABILITY.md',
            'docs/GOPAY_PRODUCT_ROUTING.md',
            'template/index.php',
            'template/api/check.php',
        ] as $relative) {
            self::assertFileExists($root . '/' . $relative);
        }
    }
}
