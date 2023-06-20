import DomAccess from 'src/helper/dom-access.helper';
import PageLoadingIndicatorUtil from 'src/utility/loading-indicator/page-loading-indicator.util';
import Plugin from 'src/plugin-system/plugin.class';

export default class Config3dPlugin extends Plugin {
    static options = {
        btnConfiguratorClass: '.btn-plugin3d',
        btnSubmit: '.btn-buy',
        configField: '#plugin3-config'
    };

    init() {
        this.configuratorBtn = DomAccess.querySelector(this.el, this.options.btnConfiguratorClass);
        this.btnSubmit = DomAccess.querySelector(this.el, this.options.btnSubmit);
        this.configField = DomAccess.querySelector(this.el, this.options.configField);

        this._registerEvents();
    }

    _registerEvents() {
        this.configuratorBtn.addEventListener('click', this._onLoadConfigurator.bind(this));
    }

    _onLoadConfigurator() {
        const config = window.m3d.getConfig();
        if (config !== null) {
            PageLoadingIndicatorUtil.create();
            this.configField.value = config;
            this.btnSubmit.click();
        }
    }
}