import template from './sw-cms-preview-image-text-row-flip.html.twig';
import './sw-cms-preview-image-text-row-flip.scss';

const { Component } = Shopware;

/**
 * @private since v6.5.0
 * @package content
 */
Component.register('sw-cms-preview-image-text-row-flip', {
    template,
});
