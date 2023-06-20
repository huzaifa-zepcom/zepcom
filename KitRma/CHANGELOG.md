# 4.3.0

- Add file size upload config

# 4.2.10

- Ticket creation and business case free-text field fixes

# 4.2.6

- Mail Template create fix

# 4.1.10

- Fix supplier ID issue if not existing

# 4.1.9

- bug fix for serial number

# 4.1.7

- Add product serial number field in ticket
- Show serial number in ticket if it was entered when creating ticket
- Add required admin flag in business case

# 4.1.3

- Select product in backend ticket using serial number
- Show selected serial number in business case

# 4.0.0

- 6.4 support

# 2.4.2

- Remove custom sender email functionality

# 2.4.1

- Adjust PDF layout with reference to barcodes

# 2.4.0

- Add field type 'serial' for business case fields
- Add serial number as barcode in pdf
- Add filter to search ticket by serial number
- Show pvg, gross price, stock in ticket view in backend
- Change item amount to show net price instead of gross

# 2.3.2

- Don't send email for internal messages
- Add supplier RMA number filter in ticket list

# 2.3.1

- Fix date format for tickets created from backend

# 2.3.0

- Updated backend interface to view uploaded files separately
- Fix bug to show attachments in comments
- Fixed sender email for customer and system emails

# 2.2.0

- Do not send customer email on creation of ticket
- Warenbegleitschein only visible on interface and not sent via email
- Show open ticket by default on backend listing
- Email template adjustments

# 2.1.2

- Add error message for invalid file upload on the ticket view page

# 2.1.0

- Changed RMA number format
- Allow zip/rar files

# 2.0.0

- Add support for B2B suite for creating ticket from order list
- Create multiple tickets for same product and order number (as long as the previous one is closed)
- Minor improvements to highlight required fields

# 1.31

- Fix for showing multiple badges in ticket backend

# 1.30

- Support for migrated orders to use product name
- Fix for non-migrated orders

# 1.21

- Allow creating ticket from customer without product in system
- Minor improvements and fixes

# 1.20

- Optimized ticket filters
- Ticket interface translation
- Fix for dropdowns
- Additional info in PDF
- Allow PDF creation if product name, supplier rma is available

# 1.13

- Can use order number for customer selection in ticket creation

# 1.12

- Add product name fallback field for tickets without product

# 1.9.12

- Fix bug when switching business case

# 1.9.10

- Fix for customer file attachments not showing in backend

# 1.9.6

- Styling of PDF

# 1.9.1

- Add additional info in PDF

# 1.9.0

- Added option to create 'Warenbegleitschein anhängen' PDF and send as attachment

# 1.8.0

- Update supplier dependency to use the correct namespace

# 1.7.0

- Customer can open RMA ticket directly from the order section from their account panel
- Add send button for backend communication module for KIT users
- Minor improvements

# 1.6.2

- Added main page data in controller actions.

# 1.6.1

- New features
  - New layout for ticket communication in frontend and backend.
  - KIT user can now change ticket business case from backend after ticket creation.
  - Add customer email in ticket to send all correspondance to that email instead.
  - New Filterset plugin dependency

- Bug fixes
  - Selectbox configuration for the business case free-text
  - Fixed checkbox info not showing for admin / frontend modules
  - Sync badge information on ticket creation
  - Allow HTML tags in the communication messages

# 1.2.4

- Fixed error in saving ticket when adding `Additional Info` data from ticket backend

# 1.2.3

- Fixed error when creating ticket from frontend

# 1.2.2

- Minor bug fix in data handling while creating ticket from backend

# 1.2.0

- Added more placeholders for text snippets
- Improved image and file handling in ticket admin module
- Other improvement and bug fixes

# 1.0.0

- Initial Plugin
