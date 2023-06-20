# SuiConvertCustomer

Plugin to convert guests into customer accounts.

### Usage

1. Use the snippet below anywhere in your _order_ related email templates to trigger the logic.

```
{% if config('SuiConvertCustomer.config.active') and order.orderCustomer.customer.guest %}
    <a class="btn btn-primary" href="{{ rawUrl('frontend.sui.guest.register', { 'id': order.orderCustomer.customerId, 'deepLinkCode': order.deepLinkCode }, salesChannel.domains|first.url) }}" role="button">Jetzt Kundenkonto eröffnen</a>
{% endif %}
```

2. Go to plugin configuration to activate the link. It will not show up in the email if it is not activated.

> The above snippet will only be shown in the email if the configuration is activated and the customer is a guest user.

**Note**: Uninstalling the plugin will not remove the snippet from your email templates, however the functionality will be disabled. 
We recommend removing the snippet to avoid unexpected behavior. 
## Requirements

| Version | Requirement     |
|-------- |---------------- |
| 1.1.4   | Shopware 6.4 >= |

## License

Please see [LICENSE](./LICENSE) file distributed with the plugin.

© SuiFactum | 2021
