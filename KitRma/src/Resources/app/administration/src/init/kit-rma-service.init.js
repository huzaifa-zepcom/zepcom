import KitRmaTicketService from '../services/kit-rma-ticket.api.service';

const { Application } = Shopware;

Application.addServiceProvider('KitRmaTicketService', (container) => {
    const initContainer = Application.getContainer('init');

    return new KitRmaTicketService(initContainer.httpClient, container.loginService);
});
