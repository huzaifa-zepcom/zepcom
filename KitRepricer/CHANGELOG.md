# 2.2.0

- Several changes for cache problem in repricer

# 2.0.6

- Cache bug fix for prices

# 2.0.4

- Use pvg custom field again instead of advance pricing

# 2.0.3

- Fix min margin bug in base rules

# 2.0.1

- Remove obsolete pvg link code

# 2.0.0

- 6.4 support
- Use advance pvg pricing instetad of custom fieldL

# 1.6.3

- Skip already processed products
- Add logs

# 1.6.1

- Adjust PVG prices based on country taxes

# 1.5.12

- Fix for out of stock product adjustment

# 1.5.11

- Fix bug for net pvg price based on customer group tax settings

# 1.5.10

- Minor bug fix

# 1.5.9

- Add `kit:pvg:link` command to generate pvg links for products

# 1.5.8

- Fixed logs and other optimizations

# 1.5.7

- Performance improvements

# 1.5.4

- Allow comma-separated product name segments for product selection
- Bug fix for raise rule execution

# 1.5.1

- Fix for excluded IDs

# 1.5.0

- Changed rules to have comma separated product numbers instead of dropdown selection
- Added option to create rules by entering product name / description segments
- Added option to create exclusion rules which excludes matched products from all other sink/raise rules
- Other optimizations and fixes

# 1.4.9

- Disable logs for production

# 1.4.8

- Fix price comparison to use gross instead of net

# 1.4.4

- Fix mysql server gone away issues for large dataset results

# 1.4.0

- Block plugin to calculate price per article for a period of time
- Set business hours for recalculating prices differently for both

# 1.3.2

- Write data bug fix

# 1.3.1

- Minor fix to reset counter

# 1.3.0

- Performance improvements
- Minor Bug fixes

# 1.2.2

- Fix margin to allow negative decimal values
- Import execution improvements

# 1.2.0

- Fixed product loading selection
- Simplified command to `kit:price`
- Performance and speed optimizations

# 1.0.0

- Initial Plugin
