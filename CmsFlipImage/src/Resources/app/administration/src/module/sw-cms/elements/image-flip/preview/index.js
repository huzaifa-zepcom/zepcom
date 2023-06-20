import template from './sw-cms-el-preview-image-flip.html.twig';
import './sw-cms-el-preview-image-flip.scss';

const { Component } = Shopware;

/**
 * @private since v6.5.0
 * @package content
 */
Component.register('sw-cms-el-preview-image-flip', {
    template,
});
