<?php
/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace Causal\IgLdapSsoAuth\Controller;

use Causal\IgLdapSsoAuth\Exception\InvalidHostnameException;
use Causal\IgLdapSsoAuth\Exception\UnresolvedPhpDependencyException;
use Causal\IgLdapSsoAuth\Utility\CompatUtility;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Routing\UriBuilder as BackendUriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\Menu\Menu;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Causal\IgLdapSsoAuth\Domain\Repository\ConfigurationRepository;
use Causal\IgLdapSsoAuth\Domain\Repository\Typo3GroupRepository;
use Causal\IgLdapSsoAuth\Domain\Repository\Typo3UserRepository;
use Causal\IgLdapSsoAuth\Library\Authentication;
use Causal\IgLdapSsoAuth\Library\Configuration;
use Causal\IgLdapSsoAuth\Library\Ldap;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Pagination\QueryResultPaginator;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Module controller.
 *
 * @author     Xavier Perseguers <xavier@causal.ch>
 * @package    TYPO3
 * @subpackage ig_ldap_sso_auth
 */
class ModuleController extends ActionController
{
    public function __construct(
        protected readonly ModuleTemplateFactory   $moduleTemplateFactory,
        protected readonly Ldap                    $ldap,
        protected readonly ConfigurationRepository $configurationRepository,
        protected readonly PageRenderer            $pageRenderer,
        protected readonly BackendUriBuilder       $backendUriBuilder,
        protected readonly IconFactory             $iconFactory,
    )
    {
    }

    public function initializeAction(): void
    {
        $this->moduleData = $this->request->getAttribute('moduleData');
        $this->moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $this->moduleTemplate->setTitle(LocalizationUtility::translate('LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang.xlf:mlang_tabs_tab'));
        $this->moduleTemplate->setFlashMessageQueue($this->getFlashMessageQueue());
    }

    /**
     * Assign default variables to ModuleTemplate view
     */
    protected function initializeView(): void
    {
        $this->moduleTemplate->assignMultiple([
            'dateFormat' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'],
            'timeFormat' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'],
        ]);
        // Load JavaScript modules
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/context-menu.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/modal.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/beuser/backend-user-listing.js');
        $this->pageRenderer->addCssFile('EXT:ig_ldap_sso_auth/Resources/Public/Css/styles.css');
    }

    public function indexAction(): ResponseInterface
    {
        $qp = $this->request->getQueryParams();
        if(isset($qp['configuration'])) {
            $configuration = $this->configurationRepository->findByUid($qp['configuration']);
        }else{
            $configuration = $this->configurationRepository->findAll()[0];
            $this->redirect('index');
        }

        $this->saveState($configuration);
        $this->populateView($configuration);
        if($configuration){
            $this->addMainMenu('index', ['configuration' => $configuration->getUid()]);
        }

        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setRouteIdentifier('txigldapssoauthM1')
            ->setArguments(['action' => 'index'])
            ->setDisplayName(LocalizationUtility::translate('LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang.xlf:mlang_tabs_tab', 'ig_ldap_sso_auth'));
        $buttonBar->addButton($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);

        return $this->moduleTemplate->renderResponse();
    }

    /**
     * Status action.
     *
     * @param \Causal\IgLdapSsoAuth\Domain\Model\Configuration|null $configuration
     * @return ResponseInterface
     */
    public function statusAction(): ResponseInterface
    {
        $qp = $this->request->getQueryParams();
        if(isset($qp['configuration'])) {
            $configuration = $this->configurationRepository->findByUid($qp['configuration']);
        }else{
            $this->redirect('index');
        }

        $this->saveState($configuration);

        Configuration::initialize(CompatUtility::getTypo3Mode(), $configuration);
        $this->populateView($configuration);
        $this->addMainMenu('status', ['configuration' => $configuration->getUid()]);

        $ldapConfiguration = Configuration::getLdapConfiguration();
        $connectionStatus = [];

        if ($ldapConfiguration['host'] !== '') {
            $ldapConfiguration['server'] = Configuration::getServerType($ldapConfiguration['server']);

            try {
                $this->ldap->connect($ldapConfiguration);
            } catch (\Exception $e) {
                // Possible known exception: 1409566275, LDAP extension is not available for PHP
                $this->addFlashMessage(
                    $e->getMessage(),
                    'Error ' . $e->getCode(),
                    ContextualFeedbackSeverity::ERROR
                );
            }

            // Never ever show the password as plain text
            $ldapConfiguration['password'] = $ldapConfiguration['password'] ? '••••••••••••' : null;

            $connectionStatus = $this->ldap->getStatus();
        } else {
            $ldapConfiguration = $this->translate('module_status.messages.ldapDisable');
        }

        $frontendConfiguration = Configuration::getFrontendConfiguration();
        if ($frontendConfiguration['LDAPAuthentication'] === false) {
            // Remove every other info since authentication is disabled for this mode
            $frontendConfiguration = ['LDAPAuthentication' => false];
        }
        $backendConfiguration = Configuration::getBackendConfiguration();
        if ($backendConfiguration['LDAPAuthentication'] === false) {
            // Remove every other info since authentication is disabled for this mode
            $backendConfiguration = ['LDAPAuthentication' => false];
        }

        $this->moduleTemplate->assign('configuration', [
            'domains' => Configuration::getDomains(),
            'ldap' => $ldapConfiguration,
            'connection' => $connectionStatus,
            'frontend' => $frontendConfiguration,
            'backend' => $backendConfiguration,
        ]);
        return $this->moduleTemplate->renderResponse();
    }

    /**
     * Search action.
     *
     * @param \Causal\IgLdapSsoAuth\Domain\Model\Configuration|null $configuration
     */
    public function searchAction(): ResponseInterface
    {
        // If configuration has been deleted
        $qp = $this->request->getQueryParams();
        if(isset($qp['configuration'])) {
            $configuration = $this->configurationRepository->findByUid($qp['configuration']);
        }else{
            $this->redirect('index');
        }

        $this->saveState($configuration);

        Configuration::initialize(CompatUtility::getTypo3Mode(), $configuration);
        $this->populateView($configuration);
        $this->addMainMenu('search', ['configuration' => $configuration->getUid()]);

        $this->pageRenderer->loadJavaScriptModule('@michalpodrouzek/ig_ldap_sso_auth/search.js');

        $frontendConfiguration = Configuration::getFrontendConfiguration();
        $this->moduleTemplate->assignMultiple([
            'baseDn' => $frontendConfiguration['users']['basedn'],
            'filter' => $frontendConfiguration['users']['filter'],
        ]);
        return $this->moduleTemplate->renderResponse();
    }

    /**
     * Import frontend users action.
     *
     * @throws RouteNotFoundException
     */
    public function importFrontendUsersAction(): ResponseInterface
    {
        return $this->importAction('fe', 'users');
    }

    /**
     * Import backend users action.
     *
     * @throws RouteNotFoundException
     */
    public function importBackendUsersAction(): ResponseInterface
    {
        return $this->importAction('be', 'users');
    }

    /**
     * Import frontend user groups action.
     *
     * @throws RouteNotFoundException
     */
    public function importFrontendUserGroupsAction(): ResponseInterface
    {
        return $this->importAction('fe', 'groups');
    }

    /**
     * Import backend user groups action.
     *
     * @throws RouteNotFoundException
     */
    public function importBackendUserGroupsAction(): ResponseInterface
    {
        return $this->importAction('be', 'groups');
    }

    /**
     * Import users or user groups action for both frontend and backend.
     *
     * @param string $type ('fe' for frontend, 'be' for backend)
     * @param string $entity ('users' or 'groups')
     * @return ResponseInterface
     * @throws RouteNotFoundException
     */
    protected function importAction(string $type, string $entity): ResponseInterface
    {
        $qp = $this->request->getQueryParams();
        if (!isset($qp['configuration'])) {
            $this->redirect('index');
        }

        $configuration = $this->configurationRepository->findByUid($qp['configuration']);
        $this->saveState($configuration);

        Configuration::initialize($type, $configuration);
        $this->populateView($configuration);

        if (!$this->checkLdapConnection()) {
            return $this->moduleTemplate->renderResponse();
        }

        $this->pageRenderer->loadJavaScriptModule('@michalpodrouzek/ig_ldap_sso_auth/import.js');

        if ($entity === 'users') {
            $data = $this->getAvailableUsers($configuration, $type);
        } else {
            $data = $this->getAvailableUserGroups($configuration, $type);
        }

        $this->moduleTemplate->assign($entity, $data);
        return $this->moduleTemplate->renderResponse();
    }

    protected function addMainMenu(string $currentAction, array $additionalParameters = []): void
    {
        $this->uriBuilder->setRequest($this->request);
        $menu = $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $menu->setIdentifier('LdapMenu');

        $menu->setLabel(LocalizationUtility::translate(
            'LLL:EXT:backend/Resources/Private/Language/locallang.xlf:modulemenu.label',
            'backend'
        ));

        // Menu items configuration
        $menuItems = [
            'index' => 'module_overview',
            'status' => 'module_status',
            'search' => 'module_search',
            'importFrontendUsers' => 'module_import_users_fe',
            'importFrontendUserGroups' => 'module_import_groups_fe',
            'importBackendUsers' => 'module_import_users_be',
            'importBackendUserGroups' => 'module_import_groups_be'
        ];

        // Helper function to create and add menu items
        foreach ($menuItems as $action => $labelKey) {
            $this->addMenuItem($menu, $action, $labelKey, $currentAction, $additionalParameters);
        }

        $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
    }

    /**
     * Helper function to add menu items to the menu.
     *
     * @param Menu $menu
     * @param string $action
     * @param string $labelKey
     * @param string $currentAction
     * @param array $additionalParameters
     */
    protected function addMenuItem($menu, string $action, string $labelKey, string $currentAction, array $additionalParameters): void
    {
        $menu->addMenuItem(
            $menu->makeMenuItem()
                ->setTitle(LocalizationUtility::translate(
                    'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang.xlf:' . $labelKey,
                    'ig_ldap_sso_auth'
                ))
                ->setHref($this->uriBuilder->uriFor($action, $additionalParameters))
                ->setActive($currentAction === $action)
        );
    }


    /**
     * Saves current state.
     *
     * @param \Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration
     */
    protected function saveState(\Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration = null)
    {
        $GLOBALS['BE_USER']->uc['ig_ldap_sso_auth']['selection'] = [
            'action' => $this->request->getControllerActionName(),
            'configuration' => $configuration !== null ? $configuration->getUid() : 0,
        ];
        $GLOBALS['BE_USER']->writeUC();
    }

    /**
     * Sort recursively an array by keys using a user-defined comparison function.
     *
     * @param array $array The input array
     * @param callable $key_compare_func The comparison function must return an integer less than, equal to, or greater than zero if the first argument is considered to be respectively less than, equal to, or greater than the second
     * @return bool Returns true on success or false on failure
     */
    protected function uksort_recursive(array &$array, $key_compare_func): bool
    {
        $ret = uksort($array, $key_compare_func);
        if ($ret) {
            foreach ($array as &$arr) {
                if (is_array($arr) && !$this->uksort_recursive($arr, $key_compare_func)) {
                    break;
                }
            }
        }
        return $ret;
    }

    /**
     * Populates the view with general objects.
     *
     * @param \Causal\IgLdapSsoAuth\Domain\Model\Configuration|null $configuration
     * @throws RouteNotFoundException
     */
    protected function populateView(\Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration = null): void
    {
        $configurationRecords = $this->configurationRepository->findAll();

        if (empty($configurationRecords)) {
            $this->handleMissingConfiguration();
            return;
        }

        // Use the first configuration record if none is provided
        $configuration = $configuration ?? $configurationRecords[0];

        // Add action buttons to the button bar
        $this->addButtonToBar($configuration);

        // Build menu items
        $menu = $this->buildMenu();

        // Assign data to the view
        $this->moduleTemplate->assignMultiple([
            'action' => $this->request->getControllerActionName(),
            'configurationRecords' => $configurationRecords,
            'currentConfiguration' => $configuration,
            'mode' => Configuration::getMode(),
            'menu' => $menu,
            'classes' => $this->getTableClasses()
        ]);
    }

    /**
     * Handle the case when there are no configuration records.
     */
    protected function handleMissingConfiguration(): void
    {
        $message = $this->translate(
            'configuration_missing.message',
            [
                'https://docs.typo3.org/typo3cms/extensions/ig_ldap_sso_auth/AdministratorManual/Index.html',
                (string)$this->backendUriBuilder->buildUriFromRoute('record_edit', [
                    'edit' => ['tx_igldapssoauth_config' => [0 => 'new']],
                    'returnUrl' => $this->request->getAttribute('normalizedParams')->getRequestUri(),
                ]),
            ]
        );
        $this->addFlashMessage(
            $message,
            $this->translate('configuration_missing.title'),
            ContextualFeedbackSeverity::WARNING
        );
    }

    /**
     * Add an edit button to the button bar.
     *
     * @param \Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration
     */
    protected function addButtonToBar($configuration): void
    {
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $editButton = $buttonBar->makeLinkButton()
            ->setIcon($this->iconFactory->getIcon('actions-document-open', Icon::SIZE_SMALL))
            ->setTitle($configuration->getName())
            ->setShowLabelText(true)
            ->setHref((string)$this->backendUriBuilder->buildUriFromRoute('record_edit', [
                'edit' => ['tx_igldapssoauth_config' => [$configuration->getUid() => 'edit']],
                'returnUrl' => $this->request->getAttribute('normalizedParams')->getRequestUri(),
            ]));

        $buttonBar->addButton($editButton);
    }

    /**
     * Build the menu items array.
     *
     * @return array
     */
    protected function buildMenu(): array
    {
        $menuItems = [
            'status' => ['titleKey' => 'module_status', 'iconName' => 'status-dialog-information'],
            'search' => ['titleKey' => 'module_search', 'iconName' => 'apps-toolbar-menu-search'],
            'importFrontendUsers' => ['titleKey' => 'module_import_users_fe', 'iconName' => 'status-user-frontend'],
            'importFrontendUserGroups' => ['titleKey' => 'module_import_groups_fe', 'iconName' => 'status-user-group-frontend'],
            'importBackendUsers' => ['titleKey' => 'module_import_users_be', 'iconName' => 'status-user-backend'],
            'importBackendUserGroups' => ['titleKey' => 'module_import_groups_be', 'iconName' => 'status-user-group-backend'],
        ];

        $menu = [];
        foreach ($menuItems as $action => $details) {
            $menu[] = [
                'action' => $action,
                'titleKey' => $details['titleKey'],
                'iconName' => $details['iconName'],
            ];
        }

        return $menu;
    }

    /**
     * Get table CSS classes.
     *
     * @return array
     */
    protected function getTableClasses(): array
    {
        return [
            'table' => 'table table-striped table-hover',
            'tableRow' => '',
        ];
    }

    /**
     * Translates a label.
     *
     * @param string $id
     * @param array $arguments
     * @return string
     */
    protected function translate(string $id, array $arguments = null): string
    {
        $value = LocalizationUtility::translate($id, 'ig_ldap_sso_auth', $arguments);
        return $value ?? $id;
    }

    /**
     * Actual search action using AJAX.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function ajaxSearch(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();

        $configuration = $this->configurationRepository->findByUid($params['configuration']);
        list($mode, $key) = explode('_', $params['type'], 2);

        Configuration::initialize($mode, $configuration);
        $config = ($mode === 'be')
            ? Configuration::getBackendConfiguration()
            : Configuration::getFrontendConfiguration();

        try {
            $success = $this->ldap->connect(Configuration::getLdapConfiguration());
        } catch (\Exception $e) {
            $success = false;
        }

        $template = GeneralUtility::getFileAbsFileName('EXT:ig_ldap_sso_auth/Resources/Private/Templates/Ajax/Search.html');
        $view = GeneralUtility::makeInstance(\TYPO3\CMS\Fluid\View\StandaloneView::class);
        $view->setFormat('html');
        $view->setTemplatePathAndFilename($template);

        if ((bool)($params['showStatus'] ?? false)) {
            $view->assign('status', $this->ldap->getStatus());
        }

        if ($success) {
            $firstEntry = (bool)($params['firstEntry'] ?? false);
            $filter = Configuration::replaceFilterMarkers($params['filter']);
            if ($firstEntry) {
                $attributes = [];
            } else {
                $attributes = Configuration::getLdapAttributes($config[$key]['mapping']);
                if (strpos($config[$key]['filter'], '{USERUID}') !== false) {
                    $attributes[] = 'uid';
                    $attributes = array_unique($attributes);
                }
            }

            $resultset = $this->ldap->search($params['baseDn'], $filter, $attributes, $firstEntry, 100);

            // With PHP 5.4 and above this could be renamed as
            // ksort_recursive($result, SORT_NATURAL)
            if (is_array($resultset)) {
                $this->uksort_recursive($resultset, 'strnatcmp');
            }

            $view->assign('resultset', $resultset);

            if ($firstEntry && is_array($resultset) && count($resultset) > 1) {
                if ($key === 'users') {
                    $mapping = $config['users']['mapping'];
                    $blankTypo3Record = Typo3UserRepository::create($params['type']);
                } else {
                    $mapping = $config['groups']['mapping'];
                    $blankTypo3Record = Typo3GroupRepository::create($params['type']);
                }
                $preview = Authentication::merge($resultset, $blankTypo3Record, $mapping, true);

                // Remove empty lines
                $keys = array_keys($preview);
                foreach ($keys as $key) {
                    if (empty($preview[$key])) {
                        unset($preview[$key]);
                    }
                }
                $view->assign('preview', $preview);
            }
        }

        $html = $view->render();

        $payload = [
            'success' => $success,
            'html' => $html,
        ];

        $response = (new JsonResponse())->setPayload($payload);

        return $response;
    }

    /**
     * Updates the search option using AJAX.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function ajaxUpdateForm(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();

        $configuration = $this->configurationRepository->findByUid($params['configuration']);
        list($mode, $key) = explode('_', $params['type'], 2);

        Configuration::initialize($mode, $configuration);
        $config = ($mode === 'be')
            ? Configuration::getBackendConfiguration()
            : Configuration::getFrontendConfiguration();

        $payload = [
            'success' => true,
            'configuration' => $config[$key],
        ];

        $response = (new JsonResponse())->setPayload($payload);

        return $response;
    }

    /**
     * Checks the LDAP connection and prepares a Flash message if unavailable.
     *
     * @return bool
     */
    protected function checkLdapConnection(): bool
    {
        try {
            $success = $this->ldap->connect(Configuration::getLdapConfiguration());
        } catch (UnresolvedPhpDependencyException $e) {
            // Possible known exception: 1409566275, LDAP extension is not available for PHP
            $this->addFlashMessage(
                $e->getMessage(),
                'Error ' . $e->getCode(),
                ContextualFeedbackSeverity::ERROR
            );
            return false;
        } catch (InvalidHostnameException $e) {
            $this->addFlashMessage(
                $e->getMessage(),
                'Error ' . $e->getCode(),
                ContextualFeedbackSeverity::ERROR
            );
            return false;
        }
        return $success;
    }

    /**
     * Returns the LDAP users with information merged with local TYPO3 users.
     *
     * @param \Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration
     * @param string $mode
     * @return array
     */
    protected function getAvailableUsers(\Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration, string $mode): array
    {
        /** @var \Causal\IgLdapSsoAuth\Utility\UserImportUtility $importUtility */
        $importUtility = GeneralUtility::makeInstance(
            \Causal\IgLdapSsoAuth\Utility\UserImportUtility::class,
            $configuration,
            $mode
        );

        $ldapInstance = Ldap::getInstance();
        $ldapInstance->connect(Configuration::getLdapConfiguration());
        $ldapUsers = $importUtility->fetchLdapUsers(false, $ldapInstance);

        $users = [];
        $numberOfUsers = 0;
        $config = ($mode === 'be')
            ? Configuration::getBackendConfiguration()
            : Configuration::getFrontendConfiguration();

        do {
            $numberOfUsers += count($ldapUsers);
            $typo3Users = $importUtility->fetchTypo3Users($ldapUsers);
            foreach ($ldapUsers as $index => $ldapUser) {
                // Merge LDAP and TYPO3 information
                $user = Authentication::merge($ldapUser, $typo3Users[$index], $config['users']['mapping']);

                // Attempt to free memory by unsetting fields which are unused in the view
                $keepKeys = ['uid', 'pid', 'deleted', 'admin', 'name', 'realName', 'tx_igldapssoauth_dn'];
                $keys = array_keys($user);
                foreach ($keys as $key) {
                    if (!in_array($key, $keepKeys)) {
                        unset($user[$key]);
                    }
                }

                $users[] = $user;
            }

            // Free memory before going on
            $typo3Users = null;
            $ldapUsers = null;

            // Current Extbase implementation does not properly handle
            // very large data sets due to memory consumption and waiting
            // time until the list starts to be "displayed". Instead of
            // waiting forever or drive code to a memory exhaustion, better
            // stop sooner than later
            if (count($users) >= 2000) {
                break;
            }

            $ldapUsers = $importUtility->hasMoreLdapUsers($ldapInstance)
                ? $importUtility->fetchLdapUsers(true, $ldapInstance)
                : [];
        } while (!empty($ldapUsers));

        $ldapInstance->disconnect();

        return $users;
    }

    /**
     * Returns the LDAP user groups with information merged with local TYPO3 user groups.
     *
     * @param \Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration
     * @param string $mode
     * @return array
     */
    protected function getAvailableUserGroups(\Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration, $mode): array
    {
        $userGroups = [];
        $config = ($mode === 'be')
            ? Configuration::getBackendConfiguration()
            : Configuration::getFrontendConfiguration();

        $ldapGroups = [];
        if (!empty($config['groups']['basedn'])) {
            $filter = Configuration::replaceFilterMarkers($config['groups']['filter']);
            $attributes = Configuration::getLdapAttributes($config['groups']['mapping']);
            $ldapInstance = Ldap::getInstance();
            $ldapInstance->connect(Configuration::getLdapConfiguration());
            $ldapGroups = $ldapInstance->search($config['groups']['basedn'], $filter, $attributes);
            $ldapInstance->disconnect();
            unset($ldapGroups['count']);
        }

        // Populate an array of TYPO3 group records corresponding to the LDAP groups
        // If a given LDAP group has no associated group in TYPO3, a fresh record
        // will be created so that $ldapGroups[i] <=> $typo3Groups[i]
        $typo3GroupPid = Configuration::getPid($config['groups']['mapping']);
        $table = ($mode === 'be') ? 'be_groups' : 'fe_groups';
        $typo3Groups = Authentication::getTypo3Groups(
            $ldapGroups,
            $table,
            $typo3GroupPid
        );

        foreach ($ldapGroups as $index => $ldapGroup) {
            $userGroup = Authentication::merge($ldapGroup, $typo3Groups[$index], $config['groups']['mapping']);

            // Attempt to free memory by unsetting fields which are unused in the view
            $keepKeys = ['uid', 'pid', 'deleted', 'title', 'tx_igldapssoauth_dn'];
            $keys = array_keys($userGroup);
            foreach ($keys as $key) {
                if (!in_array($key, $keepKeys)) {
                    unset($userGroup[$key]);
                }
            }

            $userGroups[] = $userGroup;
        }

        return $userGroups;
    }

    /**
     * Actual import of user groups using AJAX.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function ajaxGroupsImport(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();

        $configuration = $this->configurationRepository->findByUid($params['configuration']);

        $data = [];

        Configuration::initialize($params['mode'], $configuration);
        $config = ($params['mode'] === 'be')
            ? Configuration::getBackendConfiguration()
            : Configuration::getFrontendConfiguration();

        try {
            $success = $this->ldap->connect(Configuration::getLdapConfiguration());
        } catch (\Exception $e) {
            $data['message'] = $e->getMessage();
            $success = false;
        }

        if ($success) {
            list($filter, $baseDn) = explode(',', $params['dn'], 2);
            $attributes = Configuration::getLdapAttributes($config['groups']['mapping']);
            $ldapGroup = $this->ldap->search($baseDn, '(' . $filter . ')', $attributes, true);

            $pid = Configuration::getPid($config['groups']['mapping']);
            $table = $params['mode'] === 'be' ? 'be_groups' : 'fe_groups';
            $typo3Groups = Authentication::getTypo3Groups(
                [$ldapGroup],
                $table,
                $pid
            );

            // Merge LDAP and TYPO3 information
            $group = Authentication::merge($ldapGroup, $typo3Groups[0], $config['groups']['mapping']);

            if ((int)$group['uid'] === 0) {
                $group = Typo3GroupRepository::add($table, $group);
            } else {
                // Restore group that may have been previously deleted
                $group['deleted'] = 0;
                $success = Typo3GroupRepository::update($table, $group);
            }

            if (!empty($config['groups']['mapping']['parentGroup'])) {
                $fieldParent = $config['groups']['mapping']['parentGroup'];
                if (preg_match("`<([^$]*)>`", $fieldParent, $attribute)) {
                    $fieldParent = $attribute[1];

                    if (is_array($ldapGroup[$fieldParent])) {
                        unset($ldapGroup[$fieldParent]['count']);

                        $this->setParentGroup(
                            $ldapGroup[$fieldParent],
                            $fieldParent,
                            $group['uid'],
                            $pid,
                            $params['mode']
                        );
                    }
                }
            }

            $data['id'] = (int)$group['uid'];
        }

        $payload = array_merge($data, ['success' => $success]);

        $response = (new JsonResponse())->setPayload($payload);

        return $response;
    }

    /**
     * Sets the parent groups for a given TYPO3 user group record.
     *
     * @param array $ldapParentGroups
     * @param string $fieldParent
     * @param int $childUid
     * @param int $pid
     * @param string $mode
     * @throws \Causal\IgLdapSsoAuth\Exception\InvalidUserGroupTableException
     */
    protected function setParentGroup(array $ldapParentGroups, string $fieldParent, int $childUid, int $pid, string $mode)
    {
        $subGroupList = [];
        if ($mode === 'be') {
            $table = 'be_groups';
            $config = Configuration::getBackendConfiguration();
        } else {
            $table = 'fe_groups';
            $config = Configuration::getFrontendConfiguration();
        }

        foreach ($ldapParentGroups as $parentDn) {
            $typo3ParentGroup = Typo3GroupRepository::fetch($table, false, $pid, $parentDn);

            if (is_array($typo3ParentGroup[0])) {
                if (!empty($typo3ParentGroup[0]['subgroup'])) {
                    $subGroupList = GeneralUtility::trimExplode(',', $typo3ParentGroup[0]['subgroup']);
                }

                $subGroupList[] = $childUid;
                $subGroupList = array_unique($subGroupList);
                $typo3ParentGroup[0]['subgroup'] = implode(',', $subGroupList);
                Typo3GroupRepository::update($table, $typo3ParentGroup[0]);
            } else {
                $filter = '(&' . Configuration::replaceFilterMarkers($config['groups']['filter']) . '&(distinguishedName=' . $parentDn . '))';
                $attributes = Configuration::getLdapAttributes($config['groups']['mapping']);

                $ldapInstance = Ldap::getInstance();
                $ldapInstance->connect(Configuration::getLdapConfiguration());
                $ldapGroups = $ldapInstance->search($config['groups']['basedn'], $filter, $attributes);
                $ldapInstance->disconnect();
                unset($ldapGroups['count']);

                if (!empty($ldapGroups)) {
                    $pid = Configuration::getPid($config['groups']['mapping']);

                    // Populate an array of TYPO3 group records corresponding to the LDAP groups
                    // If a given LDAP group has no associated group in TYPO3, a fresh record
                    // will be created so that $ldapGroups[i] <=> $typo3Groups[i]
                    $typo3Groups = Authentication::getTypo3Groups(
                        $ldapGroups,
                        $table,
                        $pid
                    );

                    foreach ($ldapGroups as $index => $ldapGroup) {
                        $typo3Group = Authentication::merge($ldapGroup, $typo3Groups[$index], $config['groups']['mapping']);
                        $typo3Group['subgroup'] = $childUid;
                        $typo3Group = Typo3GroupRepository::add($table, $typo3Group);

                        if (is_array($ldapGroup[$fieldParent])) {
                            unset($ldapGroup[$fieldParent]['count']);

                            $this->setParentGroup(
                                $ldapGroup[$fieldParent],
                                $fieldParent,
                                $typo3Group['uid'],
                                $pid,
                                $mode
                            );
                        }
                    }
                }
            }
        }
    }

    /**
     * Actual import of users using AJAX.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function ajaxUsersImport(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();

        $configuration = $this->configurationRepository->findByUid($params['configuration']);

        /** @var \Causal\IgLdapSsoAuth\Utility\UserImportUtility $importUtility */
        $importUtility = GeneralUtility::makeInstance(
            \Causal\IgLdapSsoAuth\Utility\UserImportUtility::class,
            $configuration,
            $params['mode']
        );
        $data = [];

        Configuration::initialize($params['mode'], $configuration);
        $config = ($params['mode'] === 'be')
            ? Configuration::getBackendConfiguration()
            : Configuration::getFrontendConfiguration();

        try {
            $success = $this->ldap->connect(Configuration::getLdapConfiguration());
        } catch (\Exception $e) {
            $data['message'] = $e->getMessage();
            $success = false;
        }

        if ($success) {
            // If we assume that DN is
            // CN=Mustermann\, Max (LAN),OU=Users,DC=example,DC=com
            list($filter, $baseDn) = Authentication::getRelativeDistinguishedNames($params['dn'], 2);
            // ... we need to properly escape $filter "CN=Mustermann\, Max (LAN)" as "CN=Mustermann, Max \28LAN\29"
            list($key, $value) = explode('=', $filter, 2);
            // 1) Unescape the comma
            $value = str_replace('\\', '', $value);
            // 2) Create a proper search filter
            $searchFilter = '(' . $key . '=' . ldap_escape($value, '', LDAP_ESCAPE_FILTER) . ')';
            $attributes = Configuration::getLdapAttributes($config['users']['mapping']);
            $ldapUser = $this->ldap->search($baseDn, $searchFilter, $attributes, true);
            $typo3Users = $importUtility->fetchTypo3Users([$ldapUser]);

            // Merge LDAP and TYPO3 information
            $user = Authentication::merge($ldapUser, $typo3Users[0], $config['users']['mapping']);

            // Import the user
            $user = $importUtility->import($user, $ldapUser);

            $data['id'] = (int)$user['uid'];
        }

        $payload = array_merge($data, ['success' => $success]);

        $response = (new JsonResponse())->setPayload($payload);

        return $response;
    }
}
