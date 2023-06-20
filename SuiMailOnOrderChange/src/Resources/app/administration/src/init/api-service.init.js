import SuiOrderStatusApiService from '../core/service/sui-order-status.api.service';

const {Application} = Shopware;

Application.addServiceProvider('SuiOrderStatusApiService', (container) => {
    const initContainer = Application.getContainer('init');

    return new SuiOrderStatusApiService(initContainer.httpClient, container.loginService);
});
