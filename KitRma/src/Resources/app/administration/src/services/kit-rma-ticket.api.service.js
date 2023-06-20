const ApiService = Shopware.Classes.ApiService

/**
 * @class
 * @extends ApiService
 */
class KitRmaTicketService extends ApiService {
  constructor (httpClient, loginService, apiEndpoint = 'kit') {
    super(httpClient, loginService, apiEndpoint)
    this.name = 'KitRmaTicketService'
    this.httpClient = httpClient
  }

  /**
   * @public
   * @param route
   * @param params
   * @returns {*}
   */
  sendPost(route, params) {
    const apiRoute = `/_action/${this.getApiBasePath()}/${route}`

    return this.httpClient.post(
      apiRoute,
      params,
      {
        headers: this.getBasicHeaders()
      }
    ).then((response) => {
      return ApiService.handleResponse(response)
    })

  }

  /**
   * @public
   * @param route
   * @param params
   * @returns {*}
   */
  sendGet (route, params = {}) {
    let query = ''
    if (params) {
      query = '?' + new URLSearchParams(params).toString()
    }
    const apiRoute = `/_action/${this.getApiBasePath()}/${route}${query}`

    return this.httpClient.get(
      apiRoute,
      {
        headers: this.getBasicHeaders()
      }
    ).then((response) => {
      return ApiService.handleResponse(response)
    })

  }
}

export default KitRmaTicketService
