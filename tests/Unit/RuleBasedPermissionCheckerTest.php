<?php

namespace Agentic\Tests\Unit;

use Agentic\Permission\RuleBasedPermissionChecker;
use Agentic\Tests\TestCase;

final class RuleBasedPermissionCheckerTest extends TestCase
{
    public function test_allow_patterns_permit_matching_tools(): void
    {
        config([
            'agentic.permissions.default' => 'deny',
            'agentic.permissions.allow_patterns' => ['orders.*', 'crm.lookup'],
            'agentic.permissions.deny_patterns' => [],
        ]);

        $checker = new RuleBasedPermissionChecker();

        $this->assertTrue($checker->allows('tool:orders.read'));
        $this->assertTrue($checker->allows('tool:crm.lookup'));
        $this->assertFalse($checker->allows('tool:billing.charge'));
    }

    public function test_deny_patterns_override_allow_list(): void
    {
        config([
            'agentic.permissions.default' => 'allow',
            'agentic.permissions.allow_patterns' => ['*'],
            'agentic.permissions.deny_patterns' => ['*.delete', 'secrets.*'],
        ]);

        $checker = new RuleBasedPermissionChecker();

        $this->assertFalse($checker->allows('tool:orders.delete'));
        $this->assertFalse($checker->allows('tool:secrets.export'));
        $this->assertTrue($checker->allows('tool:orders.read'));
    }
}
