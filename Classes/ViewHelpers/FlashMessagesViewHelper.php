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

namespace Causal\IgLdapSsoAuth\ViewHelpers;

use Causal\IgLdapSsoAuth\Messaging\LdapFlashMessageRendererResolver;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Service\ExtensionService;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Render a conf* View helper which renders the flash messages (if there are any).
 *
 * @author     Xavier Perseguers <xavier@causal.ch>
 * @package    TYPO3
 * @subpackage ig_ldap_sso_auth
 */
class FlashMessagesViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * ViewHelper outputs HTML therefore output escaping has to be disabled
     *
     * @var bool
     */
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('queueIdentifier', 'string', 'Flash-message queue to use');
        $this->registerArgument('as', 'string', 'The name of the current flashMessage variable for rendering inside');
    }

    /**
     * Renders FlashMessages and flushes the FlashMessage queue
     *
     * Note: This does not disable the current page cache in order to prevent FlashMessage output
     *       from being cached.
     *       In case of conditional flash message rendering, caching must be disabled
     *       (e.g. for a controller action).
     *       Custom caching using the Caching Framework can be used in this case.
     *
     * @return mixed
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $as = $arguments['as'];
        $queueIdentifier = $arguments['queueIdentifier'];

        if ($queueIdentifier === null) {
            /** @var RenderingContext $renderingContext */
            $request = $renderingContext->getRequest();
            if (!$request instanceof RequestInterface) {
                // Throw if not an extbase request
                throw new \RuntimeException(
                    'ViewHelper f:flashMessages needs an extbase Request object to resolve the Queue identifier magically.'
                    . ' When not in extbase context, set attribute "queueIdentifier".',
                    1639821269
                );
            }
            $extensionService = GeneralUtility::makeInstance(ExtensionService::class);
            $pluginNamespace = $extensionService->getPluginNamespace($request->getControllerExtensionName(), $request->getPluginName());
            $queueIdentifier = 'extbase.flashmessages.' . $pluginNamespace;
        }

        $flashMessageQueue = GeneralUtility::makeInstance(FlashMessageService::class)->getMessageQueueByIdentifier($queueIdentifier);
        $flashMessages = $flashMessageQueue->getAllMessagesAndFlush();
        if (count($flashMessages) === 0) {
            return '';
        }

        if ($as === null) {
            return GeneralUtility::makeInstance(LdapFlashMessageRendererResolver::class)->resolve()->render($flashMessages);
        }
        $templateVariableContainer = $renderingContext->getVariableProvider();
        $templateVariableContainer->add($as, $flashMessages);
        $content = $renderChildrenClosure();
        $templateVariableContainer->remove($as);

        return $content;
    }
}
