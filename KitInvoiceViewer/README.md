<h1>KIT Invoice Viewer</h1>

Shows the Invoice for orders in the customer account. For that the Plugin is recognizing PDF invoices by their order number from a directory that can be specified in the plugin configuration.

<h2>Plugin Configuration</h2>

After installing the plugin, you need to go to the plugin configuration and specify the path, in which the invoices are located. By default the path will start at the root directory of shopware and there by the filter "Import".

<h2>Invoice Format</h2>
In order for the plugin to find the invoices, they need to be in the following format:

<code>FXXXXXXX_Ordernumber.pdf</code>

So every invoice needs to be a pdf file, start with n F, followed by the 7 Digit invoice number, then a blank "_", followed by the ordernumber.

If the Format is correct, the customers can view their orders in their customer accounts.