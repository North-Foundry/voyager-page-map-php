<?php

declare(strict_types=1);

namespace NorthFoundry\VoyagerPageMap\Tests\Unit\Configuration;

use InvalidArgumentException;
use NorthFoundry\VoyagerPageMap\Configuration\VoyagerPageMapConfiguration;
use PHPUnit\Framework\TestCase;

/**
 * Verifies the immutable public API exposed by VPM configuration profiles.
 */
final class VoyagerPageMapConfigurationTest extends TestCase
{
    public function testProfilesExposeExpectedDefaults(): void
    {
        self::assertEquals(VoyagerPageMapConfiguration::agent(), VoyagerPageMapConfiguration::default());
        self::assertEquals(VoyagerPageMapConfiguration::agent(), VoyagerPageMapConfiguration::compact());
        self::assertFalse(VoyagerPageMapConfiguration::agent()->includeHiddenElements);
        self::assertFalse(VoyagerPageMapConfiguration::agent()->includeAttributes);
        self::assertFalse(VoyagerPageMapConfiguration::agent()->resolveRelativeUrls);
        self::assertTrue(VoyagerPageMapConfiguration::diagnostic()->includeHiddenElements);
        self::assertTrue(VoyagerPageMapConfiguration::diagnostic()->includeGenericContainers);
        self::assertTrue(VoyagerPageMapConfiguration::diagnostic()->includeAttributes);
    }

    public function testFluentMethodsReturnAnIndependentConfiguration(): void
    {
        $agent = VoyagerPageMapConfiguration::agent();
        $configured = $agent
            ->withHiddenElements()
            ->withGenericContainers()
            ->withAttributes([' ID ', 'data-tracking', 'id'])
            ->withRelativeUrlResolution();

        self::assertNotSame($agent, $configured);
        self::assertFalse($agent->includeHiddenElements);
        self::assertFalse($agent->includeGenericContainers);
        self::assertFalse($agent->includeAttributes);
        self::assertSame(['id', 'data-tracking'], $configured->includeAttributes);
        self::assertTrue($configured->includeHiddenElements);
        self::assertTrue($configured->includeGenericContainers);
        self::assertTrue($configured->resolveRelativeUrls);
        self::assertInstanceOf(VoyagerPageMapConfiguration::class, VoyagerPageMapConfiguration::agent()->withAttributes());
    }

    public function testInvalidAttributeSelectionIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new VoyagerPageMapConfiguration(includeAttributes: ['id', '']);
    }
}
