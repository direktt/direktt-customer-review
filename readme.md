# Direktt Customer Review

A powerful WordPress plugin for prompting users to give you feedback on service, tightly integrated with the [Direktt WordPress Plugin](https://direktt.com/).

With Customer Review extension you can:

- **Send customizable message templates** to customers including interactive message for review submission right from the chat interface.
- **Review based response templates** to respond diferrently to positive and negative reviews
- **Browse full user reviews history** for every user via Direktt mobile app or wp-admin.

## Documentation

You can find the detailed plugin documentation, guides and tutorials in the Wiki section:  
https://github.com/direktt/direktt-customer-review/wiki

## Requirements

- WordPress 5.6 or higher
- The [Direktt Plugin](https://wordpress.org/plugins/direktt/) (must be active)

## Installation

1. Install and activate the **Direktt** core plugin.
2. Download the direktt-customer-review.zip from the latest [release](https://github.com/direktt/direktt-customer-review/releases)
2. Upload **direktt-customer-review.zip** either through WordPress' **Plugins > Add Plugin > Upload Plugin** or upload the contents of this direktt-customer-review.zip to the `/wp-content/plugins/` directory of your WordPress installation.
3. Activate **Direktt Customer Review** from your WordPress plugins page.
4. Configure the plugin under **Direktt > Settings > Customer Review Settings**.

## Usage

### Plugin Settings

- Find **Direktt > Settings > Customer Review Settings** in your WordPress admin menu.
- Configure:
    - Message which will be sent to subscriber when Customer Review workflow is triggered.
    - Minimum, maximum and threshold rating. 
    - Notification sent to the subscriber when review is submitted based on the rating and treshold (you can send different post review messages for positive and negative reviews)
    - Notification for channel admin on review submission

### Workflow

- **User receives interactive review message**
    - Upon scanning the Review action QR code
    - OR initiated manually by channel admin in Direktt mobile app
- **User leaves the rating**
    - User taps the respective rating button in chat interface    
- **User receives feedback message**.
    - Based on the rating, the user receives respective message. If the rating is above the treshold, user can be invited to submit the review on e.g. Google Reviews. If the rating is below the treshold, user can be asked to provide additional details
- **If so configured, channel admin receives notification** and can immediately initiate the chat with the user
- All user's previous ratings are available in the **user profile** within Direktt mobile app.

## Notification Templates

Direktt Message templates support following dynamic placeholders:

- `#display_name#` — display name of the new subsriber (only for admin message)
- `#subscriptionId#` — subscription id of the new subscriber (only for admin message)
- `#rating#` — rating that user selected (only for admin message)

## Recent Reviews

For every review, an entry is made with rating and timestamp.

---

## Updating

The plugin supports updating directly from this GitHub repository.

---

## License

GPL-2.0-or-later

---

## Support

Contact [Direktt](https://direktt.com/) for questions, issues, or contributions.
