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

namespace Causal\IgLdapSsoAuth\Messaging\Renderer;

use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\Renderer\BootstrapRenderer;
use TYPO3\CMS\Core\Messaging\Renderer\FlashMessageRendererInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A class representing a bootstrap flash messages.
 * This class renders flash messages as markup, based on the
 * bootstrap HTML/CSS framework. It is used in backend context.
 * The created output contains all classes which are required for
 * the TYPO3 backend. Any kind of message contains also a nice icon.
 */
class LdapBootstrapRenderer extends BootstrapRenderer
{
    /**
     * Gets the message rendered as clean and secure markup
     *
     * @param FlashMessage[] $flashMessages
     */
    protected function getMessageAsMarkup(array $flashMessages): string
    {
        $markup = [];
        $markup[] = '<div class="typo3-messages">';
        foreach ($flashMessages as $flashMessage) {
            $messageTitle = $flashMessage->getTitle();
            $markup[] = '<div class="alert ' . htmlspecialchars($this->getClass($flashMessage)) . '">';
            $markup[] = '  <div class="media">';
            $markup[] = '    <div class="media-left">';
            $markup[] = '      <span class="icon-emphasized">';
            $markup[] =            $this->iconFactory->getIcon($this->getIconName($flashMessage), Icon::SIZE_SMALL)->render();
            $markup[] = '      </span>';
            $markup[] = '    </div>';
            $markup[] = '    <div class="media-body">';
            if ($messageTitle !== '') {
                $markup[] = '      <div class="alert-title">' . htmlspecialchars($messageTitle) . '</div>';
            }
            $markup[] = '      <p class="alert-message">' . $flashMessage->getMessage() . '</p>';
            $markup[] = '    </div>';
            $markup[] = '  </div>';
            $markup[] = '</div>';
        }
        $markup[] = '</div>';
        return implode('', $markup);
    }
}
