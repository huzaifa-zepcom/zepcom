const ApiService = Shopware.Classes.ApiService;

/**
 * @class
 * @extends ApiService
 */
class SuiOrderStatusApiService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'sui/order-status') {
        super(httpClient, loginService, apiEndpoint);
        this.name = 'SuiOrderStatusApiService';
        this.httpClient = httpClient;
    }

    /**
     * @public
     * @param route
     * @param params
     * @returns {*}
     */
    sendPost(route, params) {
        const apiRoute = `/_action/${this.getApiBasePath()}/${route}`;

        return this.httpClient.post(
            apiRoute,
            params,
            {
                headers: this.getBasicHeaders(),
            },
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }
}

export default SuiOrderStatusApiService;
