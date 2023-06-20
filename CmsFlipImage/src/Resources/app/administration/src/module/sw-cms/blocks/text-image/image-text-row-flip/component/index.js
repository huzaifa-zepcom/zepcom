import template from './sw-cms-block-image-text-row-flip.html.twig';
import './sw-cms-block-image-text-row-flip.scss';

const { Component } = Shopware;

/**
 * @private since v6.5.0
 * @package content
 */
Component.register('sw-cms-block-image-text-row-flip', {
    template,
});
