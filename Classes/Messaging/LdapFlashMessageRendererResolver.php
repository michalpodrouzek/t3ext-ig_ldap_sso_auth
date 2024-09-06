<?php

declare(strict_types=1);

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

namespace Causal\IgLdapSsoAuth\Messaging;

use Causal\IgLdapSsoAuth\Messaging\Renderer\LdapBootstrapRenderer;
use TYPO3\CMS\Core\Messaging\FlashMessageRendererResolver;
use TYPO3\CMS\Core\Messaging\Renderer\FlashMessageRendererInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A class for rendering flash messages.
 */
class LdapFlashMessageRendererResolver extends FlashMessageRendererResolver
{
    /**
     * This method resolves a FlashMessageRendererInterface for the given $context.
     *
     * In case $context is null, the context will be detected automatic.
     */
    public function resolve(): FlashMessageRendererInterface
    {
        $renderer = GeneralUtility::makeInstance(LdapBootstrapRenderer::class);
        if (!$renderer instanceof FlashMessageRendererInterface) {
            throw new \RuntimeException('Renderer ' . get_class($renderer)
                . ' does not implement FlashMessageRendererInterface', 1476958086);
        }
        return $renderer;
    }
}
