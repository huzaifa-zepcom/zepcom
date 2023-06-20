import KitPriceUpdateApiService from '../core/service/api/kit-price-update.api.service';

const { Application } = Shopware;

Application.addServiceProvider('KitPriceUpdateApiService', (container) => {
    const initContainer = Application.getContainer('init');

    return new KitPriceUpdateApiService(initContainer.httpClient, container.loginService);
});
