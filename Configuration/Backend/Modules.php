<?php

return [
    'txigldapssoauthM1' => [
        'parent' => 'system',
        'position' => ['top'],
        'access' => 'admin',
        'workspaces' => 'live',
        'path' => '/module/system/ldap',
        'extensionName' => 'ig_ldap_sso_auth',
        'iconIdentifier' => 'ldap-module-icon',
        'labels' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang.xlf',
        'controllerActions' => [
            \Causal\IgLdapSsoAuth\Controller\ModuleController::class => implode(',', [
                'index',
                'status',
                'search',
                'importFrontendUsers', 'importBackendUsers',
                'importFrontendUserGroups', 'importBackendUserGroups',
            ])
        ],
    ],
];
