<h1>KIT Auto Price Update</h1>

Prerequisites:
- <a href="https://git.klarsicht-it.de/klarsicht-it/KitDataImport">KitDataImport</a>
- <a href="https://git.klarsicht-it.de/klarsicht-it/KitSupplier">KitSupplier</a>

The Plugin was built for recalculating prices in our shopware 6 shop by comparing our current prices with the ones from our competitors at the price comparison site "Geizhals".

<h2>Plugin Installation / Initial Setup</h2>
After the plugin has been installed, you first need to go to the plugin settings and check, if the directory for the Geizhals file will be correct.

By default the path is "export/export.geizhals.at/kunden-download/klarsichtit/x_daten.csv" starting from the shopware root directory.

THe Plugin Settings can be found in "Settings -> System -> Plugins -> KIT Repricer: Three Dots -> Config"

<h2>Setting up the Plugin</h2>
After the Plugin has been configured it is time to set it up. You can find the settings for the plugin in "Settings -> Plugins -> KIT Repricer".

<h3>But before we start, here are some general informations about the functionality and how the plugin is working</h3>

The Plugin is working with rules, that define, how to calculate specific prices for specific products.

For that there are in general two functions: The "Sink" function, which lowers prices and the "Raise" function which is lifting the prices up.

Each of these functions have a main rule and exception rules. So if the plugin is checking the prices, it will first execute all exception rules and then apply the main rule for all products that are left.

<h2>Sink Function</h2>
The goal of the sink rule / function in general is, to adjust the price of a product, so that we can have a better price than the ones from our competitors. The Plugin is accomplishing this
by comparing the current price of our shop (which is getting imported from COP by the <a href="https://git.klarsicht-it.de/klarsicht-it/KitDataImport">KitDataImport</a>) with the ones listed in the geizhals file.

In the Geizhals file are all prices listed for a product, that our competitors offer.

So, after clicking on "KIT Repricer", the plugin will open the general sink / raise rule page. You can select the one you would like to edit in the dropdown, by default the Sink Function is selected.

For the sink rule you will find some fields, that will help you select the products that should be touched by this rule as precisely as possible.

The required files for this rule are: 

* Mindestpreis (Minimum Price)                                  -> The minimum Price, the product should have
* Mindestmarge (Minimum margin)                                 -> The minimum margin, that we should have left when calculating the new price
* Abstand zum n.H. (Difference to the next higher competitor)   -> The difference to the next higher competitor by price.*
                                                                
*Explanation: The explanation of this field is a bit more complicated:
So the repricer in general is only adjusting prices as far as it has to. Means to say that if our lowest price possible is 1000 and the best pricce from our competitor is 1100, the Plugin will 
adjust the price by default also to 1100, as we than would also be at position 1.

If you now add a value of 5 to the field "Abstand zum n.H.", the plugin will lower the price 5€ more, so for our example it will lower it to 1095. That way we are ahead of our competitor by 5€.

If you now hit "save", the rules will be applied by the next Repricer Execution.

<h2>Raise Function</h2>
The goal of the raise rule / function in general is, to adjust the price of a product if we have a price that is too low in comparison of the ones from our competitors. Means to say
that if we are already at position 1 in the price comparison, the raise function is checking, what the price of the competitor from position 2 is and then adjusting the price, if possible / neccessary.

The required fields for this rule are:
* Min. Price (Minimum Price)    -> The minimum Price, the product should have
* Comparative difference        -> Difference between our price and the price of position two. If this value is 10, our price is 100, the one of pos. 2 is 110, the rule will be applied. If pos. 2 would be 108, it would not be applied (as the difference is not bigger than 10)
* Distance to n.H               -> Distance, that the price should be adjustet to. If value has 2, our price is 100, pos 2. has 110, the price would be adjusted to 108, so that there is a difference of two between our new price and the one from pos 2.


<h2>Exception Rules</h2>
The Exception Rules are to adjust prices for specific products different to the main rule. This can be useful for some manufacturers, as we get better prices for their products as for other products.

If a Product is being specified in an exception, it won't be calculated in the main rule again, as this would overwrite the exception rule.

To create a new exception rule, you can click "Create" on the bottom of the main sink / raise rule. 

After that a new window will open. In there you can type the name of the exception rule and select, if this should be an exception for sink or raise (according to the selection you will have different fields as it is with the main rules).

Now you can select the product as precisely as you want to by Manufacturer, Category, Supplier, Product NUmber, Product Name, Description, etc.

Also you have the possibility, to exclude the selected products by selecting "Ausgeschlossen". **If this switch is being activated, all products defined in this rule will be excluded by the repricer completely.**

Also there are some fields that are important to explain:

* Priorität                                                     -> Priority of this rule. Is telling the Repricer, in which order the rules have to be executed. Higher is better, 1 is the lowest priority. Priority is regardless of the type of rule.
* Mindestpreis (Minimum Price)                                  -> The minimum Price, the product should have
* Mindestmarge (Minimum margin)                                 -> The minimum margin, that we should have left when calculating the new price (only for sink)
* Abstand zum n.H. (Difference to the next higher competitor)   -> The difference to the next higher competitor by price (as explained above) (only for sink)
* Maximaler Platz                                               -> Only Optimize Price until we reach this position in price comparison ranking (only for sink)
* Comparative difference                                        -> Difference between our price and the price of position two (only for raise)
* Distance to n.H                                               -> Distance, that the price should be adjustet to (only for raise)

<h2>Running the Repricer</h2>

After the rules have been applied, you need to run the repricer command via console so that the prices will get calculated. On each run, the repricer will import the prices from the Geizhals file, and then compare them with the ones from our shop.

For that he will use the custom field "PVG" from every product. The PVG price is by default being written by the import plugin and is the same as the normal price with tax.

If the repricer is calculating a new price for a product, it will only change the price in the pvg field, not the normal one. Because of that he will also use this price for the next comparison.

To view all commands, you can simply run

<code>bin/console kit:price -h</code>

This will list all available commands for this command.

Before every run, you need to execute 

<code>bin/console kit:price -i</code>

This will import the geizhals file.

After that you have the possibility to run

<code>bin/console kit:price -s</code>

to execute the sink function with it's exception rules and

<code>bin/console kit:price -r</code> 

to execture the raise function and it's exception rules.

You can also just run 

<code>bin/console kit:price</code>

to execute the import, the sink and raise rule.

By this command, the sink rule will always be applied before the raise rule.

The Repricer is recognising each tax setting for the regarding products. If a product has 16%, it will be calculated with 16%, if it has 19%, it will be calculated with 19, etc.

Once the import is done, the PVG price will be updated for each price individually.

<h2>The Log and Calculations</h2>
While the Repricer is running or after the Repricer has been executed, there is a log being created in the calculation settings in the admin panel.

If you click on "Logs" on the right upper corner of the plugin (next to save), you will see the log of the repricer with an explanation, which product has been touched and how it has been calculated.

Important for the log and the sink rule are the columns Best Competitor, Original Price, Optimized Price, Minimum Price, Margin, Rule.

These Columns will tell you, what the competitor price is, what our original price was, what the new price is and what the minimum Price for this product is.

The Minimum Price is the lowest price we can sell the product for. It is being calculated like this:

<code>(PurchasePrice Gross / 100 * Margin) + Purchase Price Gross = Minimum Price</code>

And from this price on we can adjust our prices according to our competitors.

The Margin is the one that has been defined in the regarding sink rule.

<h2>Showing Prices in the Frontend</h2>
The Prices calculated by the Repricer will not be displayed in the frontend by default. The main function of the plugin is to give these prices only to customers, that visit the shop form a price comparison website.

Due to that the prices can't be viewed by a normal customer. If a customer is visiting our page from a website like "Geizhals", he will view the product detail page extended with a has value. That has value will be recognised and the PVG Price will be shown (but only for this product!) instead of the normal price.

But there is one more possibility, to show PVG Prices to customers. That is by going into the customer settings of the regarding customer, and enabling "PVG Enabled" in the Custom Field "kit_customer".

A description on how to edit a customer account can be found <a href="https://docs.shopware.com/en/shopware-6-en/customers/overview#edit-a-customer">here</a>.

**The customer has to be logged in, if he wants to see the prices then**

<h2>Debugging</h2>
To check the prices for the frontend, you can extend the URL of the current product you want to check with "?repricer". If you add this, there will be the normal price displayed, as well as the pvg price with tax and without tax. 

For debugging, you can also set the shop to the dev mode. You can find out how to do this <a href="https://docs.shopware.com/en/en/shopware-6-en/tutorials-and-faq/debugging?category=shopware-6-en/tutorials-and-faq#activate-the-debug-or-developer-mode">here</a>.

If the shop is in the dev mode, the plugin will display more detailed information while executing the commands 

<code>bin/console kit:price -i</code>

<code>bin/console kit:price -s</code>

<code>bin/console kit:price -r</code>

in the console.

Also there will be a log folder created in the plugin directory in "KitAutoPriceUpdate/src/Logs" where you can get the detailed information as well.

The Dev Mode should only be turned on if necessary, as this will slow down the Plugin as well as the shop massively.

