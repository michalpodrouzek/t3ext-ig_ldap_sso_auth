<?php
return [
    'ctrl' => [
        'title' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config',
        'label' => 'name',
        'sortby' => 'sorting',
        'adminOnly' => 1,
        'rootLevel' => 1,
        'dividers2tabs' => true,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'iconfile' => 'EXT:ig_ldap_sso_auth/Resources/Public/Icons/icon_tx_igldapssoauth_config.png',
    ],
    'types' => [
        '1' => [
            'showitem' => '
                    --div--;GENERAL,
                        hidden, name, sites,
                    --div--;LDAP,
                        ldap_server, ldap_charset,
                    --palette--;LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:palette.connection;connection,
                        ldap_binddn, ldap_password, group_membership,
                    --div--;FE_USERS,
                        fe_users_basedn, fe_users_filter, fe_users_mapping, fe_groups_required, fe_groups_assigned,
                    --div--;FE_GROUPS,
                        fe_groups_basedn, fe_groups_filter, fe_groups_mapping,
                    --div--;BE_USERS,
                        be_users_basedn, be_users_filter, be_users_mapping, be_groups_required, be_groups_assigned,
                    --div--;BE_GROUPS,
                        be_groups_basedn, be_groups_filter, be_groups_mapping, be_groups_admin'
        ],
    ],
    'palettes' => [
        'connection' => [
            'showitem' => 'ldap_host, ldap_port, ldap_tls, ldap_tls_reqcert, ldap_ssl',
            'canNotCollapse' => 1,
        ],
    ],
    'columns' => [
        'hidden' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
            ],
        ],
        'name' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.name',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'required' => true,
                'eval' => 'trim',
            ]
        ],
        'domains' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.domains',
            'description' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.domains.v9_description',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'itemsProcFunc' => \Causal\IgLdapSsoAuth\Tca\DomainItemsProcFunc::class . '->getDomains',
                'size' => 10,
                'default' => '',
            ]
        ],
        'sites' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.sites',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'itemsProcFunc' => \Causal\IgLdapSsoAuth\Tca\SiteConfigurationItemsProcFunc::class . '->getSites',
                'allowNonIdValues' => true,
                'size' => 10,
            ]
        ],
        'ldap_server' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.ldap_server',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.ldap_server.I.0',
                        'value' => \Causal\IgLdapSsoAuth\Library\Configuration::SERVER_OPENLDAP
                    ],
                    [
                        'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.ldap_server.I.1',
                        'value' =>  \Causal\IgLdapSsoAuth\Library\Configuration::SERVER_ACTIVE_DIRECTORY
                    ],
                ],
                'size' => 1,
                'maxitems' => 1,
            ],
            'onChange' => 'reload',
        ],
        'ldap_charset' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.ldap_charset',
            'config' => [
                'type' => 'input',
                'size' => '10',
                'max' => '255',
                'eval' => 'trim',
                'default' => 'utf-8',
            ]
        ],
        'ldap_host' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.ldap_host',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'max' => '255',
                'eval' => 'trim',
            ]
        ],
        'ldap_port' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.ldap_port',
            'config' => [
                'type' => 'number',
                'size' => '5',
                'max' => '5',
                'eval' => 'trim',
                'default' => '389',
            ]
        ],
        'ldap_tls' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.ldap_tls',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
            ],
        ],
        'ldap_tls_reqcert' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.ldap_tls_reqcert',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 1,
            ],
        ],
        'ldap_ssl' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.ldap_ssl',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
            ],
        ],
        'ldap_binddn' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.ldap_binddn',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            ]
        ],
        'ldap_password' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.ldap_password',
            'config' => [
                'type' => 'password',
                'size' => '30',
                'max' => '255',
            ]
        ],
        'group_membership' => [
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.group_membership',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.group_membership.I.1',
                        'value' => \Causal\IgLdapSsoAuth\Library\Configuration::GROUP_MEMBERSHIP_FROM_GROUP,
                        'icon' => 'EXT:ig_ldap_sso_auth/Resources/Public/Icons/selicon_group_membership_1.png'
                    ],
                    [
                        'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.group_membership.I.2',
                        'value' => \Causal\IgLdapSsoAuth\Library\Configuration::GROUP_MEMBERSHIP_FROM_MEMBER,
                        'icon' => 'EXT:ig_ldap_sso_auth/Resources/Public/Icons/selicon_group_membership_2.png'
                    ],
                ],
                'minitems' => 1,
                'maxitems' => 1,
                'default' => 1,
                'fieldWizard' => [
                    'selectIcons' => [
                        'disabled' => false,
                    ],
                ],
            ],
        ],
        'be_users_basedn' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.be_users_basedn',
            'config' => [
                'type' => 'input',
                'renderType' => 'ldapSuggest',
                'size' => '30',
                'eval' => 'trim',
            ]
        ],
        'be_users_filter' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.be_users_filter',
            'config' => [
                'type' => 'text',
                'renderType' => 'ldapSuggest',
                'rows' => 3,
                'cols' => 30,
                'eval' => 'trim',
            ]
        ],
        'be_users_mapping' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.be_users_mapping',
            'config' => [
                'type' => 'text',
                'renderType' => 'ldapSuggest',
                'rows' => 8,
                'cols' => 30,
                'eval' => 'trim',
            ]
        ],
        'be_groups_basedn' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.be_groups_basedn',
            'config' => [
                'type' => 'input',
                'renderType' => 'ldapSuggest',
                'size' => '30',
                'eval' => 'trim',
            ]
        ],
        'be_groups_filter' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.be_groups_filter',
            'config' => [
                'type' => 'text',
                'renderType' => 'ldapSuggest',
                'rows' => 3,
                'cols' => 30,
                'eval' => 'trim',
            ]
        ],
        'be_groups_mapping' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.be_groups_mapping',
            'config' => [
                'type' => 'text',
                'renderType' => 'ldapSuggest',
                'rows' => 8,
                'cols' => 30,
                'eval' => 'trim',
            ]
        ],
        'be_groups_required' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.be_groups_required',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'be_groups',
                'foreign_table_where' => 'AND be_groups.tx_igldapssoauth_dn<>\'\' AND be_groups.tx_igldapssoauth_dn IS NOT NULL ORDER BY be_groups.title',
                'size' => 6,
                'minitems' => 0,
                'maxitems' => 30,
            ]
        ],
        'be_groups_assigned' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.be_groups_assigned',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'be_groups',
                'foreign_table_where' => 'AND (be_groups.tx_igldapssoauth_dn=\'\' OR be_groups.tx_igldapssoauth_dn IS NULL) ORDER BY be_groups.title',
                'size' => 10,
                'minitems' => 0,
                'maxitems' => 30,
            ]
        ],
        'be_groups_admin' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.be_groups_admin',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'be_groups',
                'foreign_table_where' => 'ORDER BY be_groups.title',
                'size' => 6,
                'minitems' => 0,
                'maxitems' => 30,
            ]
        ],
        'fe_users_basedn' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.fe_users_basedn',
            'config' => [
                'type' => 'input',
                'renderType' => 'ldapSuggest',
                'size' => '30',
                'eval' => 'trim',
            ]
        ],
        'fe_users_filter' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.fe_users_filter',
            'config' => [
                'type' => 'text',
                'renderType' => 'ldapSuggest',
                'rows' => 3,
                'cols' => 30,
                'eval' => 'trim',
            ]
        ],
        'fe_users_mapping' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.fe_users_mapping',
            'config' => [
                'type' => 'text',
                'renderType' => 'ldapSuggest',
                'rows' => 8,
                'cols' => 30,
                'eval' => 'trim',
            ]
        ],
        'fe_groups_basedn' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.fe_groups_basedn',
            'config' => [
                'type' => 'input',
                'renderType' => 'ldapSuggest',
                'size' => '30',
                'eval' => 'trim',
            ]
        ],
        'fe_groups_filter' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.fe_groups_filter',
            'config' => [
                'type' => 'text',
                'renderType' => 'ldapSuggest',
                'rows' => 3,
                'cols' => 30,
                'eval' => 'trim',
            ]
        ],
        'fe_groups_mapping' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.fe_groups_mapping',
            'config' => [
                'type' => 'text',
                'renderType' => 'ldapSuggest',
                'rows' => 8,
                'cols' => 30,
                'eval' => 'trim',
            ]
        ],
        'fe_groups_required' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.fe_groups_required',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'fe_groups',
                'foreign_table_where' => 'AND fe_groups.tx_igldapssoauth_dn<>\'\' AND fe_groups.tx_igldapssoauth_dn IS NOT NULL ORDER BY fe_groups.title',
                'size' => 10,
                'minitems' => 0,
                'maxitems' => 99,
            ]
        ],
        'fe_groups_assigned' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.fe_groups_assigned',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'fe_groups',
                'foreign_table_where' => 'AND (fe_groups.tx_igldapssoauth_dn=\'\' OR fe_groups.tx_igldapssoauth_dn IS NULL) ORDER BY fe_groups.title',
                'size' => 10,
                'minitems' => 0,
                'maxitems' => 99,
            ]
        ],
    ],
];
