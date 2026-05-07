<?php

namespace App\Libraries;

class PermConfig
{
    // Per-module permissions configurable per role.
    // 'input'  = true → can_input_{key} perm (create/edit)
    // 'delete' = true → can_delete_{key} perm
    const MODULE_PERMS = [
        'traffic'       => ['label' => 'Daily Traffic',      'input' => true,  'delete' => false],
        'content'       => ['label' => 'Content & Rundown',  'input' => true,  'delete' => false],
        'loyalty'       => ['label' => 'Program Loyalty',    'input' => true,  'delete' => true],
        'vm'            => ['label' => 'Dekorasi & VM',       'input' => true,  'delete' => false],
        'creative'      => ['label' => 'Creative & Design',  'input' => true,  'delete' => true],
        'exhibitors'    => ['label' => 'Exhibition',          'input' => true,  'delete' => false],
        'sponsors'      => ['label' => 'Sponsorship',         'input' => true,  'delete' => false],
        'budget'        => ['label' => 'Budget',              'input' => false, 'delete' => false],
        'summary'       => ['label' => 'Event Summary',       'input' => false, 'delete' => false],
        'tracking'      => ['label' => 'Daily Tracking',      'input' => true,  'delete' => true],
        'baseline'      => ['label' => 'Baseline',            'input' => true,  'delete' => false],
        'inputs'        => ['label' => 'Event Config',        'input' => true,  'delete' => false],
        'tenants'       => ['label' => 'Tenants',             'input' => true,  'delete' => true],
        'tenant_impact' => ['label' => 'Tenant Impact',       'input' => true,  'delete' => false],
    ];
}
