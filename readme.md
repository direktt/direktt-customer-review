# Direktt Customer Review

A powerful WordPress plugin for prompting users to give you feedback on service, tightly integrated with the [Direktt WordPress Plugin](https://direktt.com/).

- **Send customizable message templates** to users containing prompt for reviewing.
- **Review full user reviews history** for every user via wp-admin or user profile tool.

## Requirements

- WordPress 5.0 or higher
- The [Direktt Plugin](https://wordpress.org/plugins/direktt/) (must be active)

## Installation

1. Install and activate the **Direktt** core plugin.
2. Download the direktt-customer-review.zip from the latest [release](https://github.com/direktt/direktt-customer-review/releases)
2. Upload **direktt-customer-review.zip** either through WordPress' **Plugins > Add Plugin > Upload Plugin** or upload the contents of this direktt-customer-review.zip to the `/wp-content/plugins/` directory of your WordPress installation.
3. Activate **Direktt Customer Review** from your WordPress plugins page.
4. Configure the plugin under **Direktt > Settings > Customer Review Settings**.

## Usage

### Admin Interface

- Find **Direktt > Settings > Customer Review Settings** in your WordPress admin menu.
- Configure:
    - Choose the message template which will be sent to subscriber when Customer Review is prompted.
    - Set up minimum, maximum and threshold rating.
    - Choose the message templates which will be sent to subscriber when review is submitted by them.
    - Do you want Direktt Admin to be notified when review is submitted?
    - Choose the message template which will be sent to Direktt Admin when review is submitted.

### Points Management

- Access a user profile via the Direktt User profile or wp-admin.
- Add or remove points using configured rules.
- Reset user points to initial value when users reedem Awards.
- All actions are logged in the user’s **transaction history**.

### Shortcode (Front End)

Show the loyalty points account and recent transaction history to a Direktt user:

```[direktt_loyalty_program_service]```

## Notification Templates

Direktt Message templates support following dynamic placeholders:

- `#change#` — number of points added/removed
- `#points#` — new points balance
- Other admin templates: `#display_name#`, `#subscription_id#`

## Transaction Logs

For every points change or reset, an entry is made with admin name, change amount, balance, and timestamp.

---

## Updating

The plugin supports updating directly from this GitHub repository.

---

## License

GPL-2.0-or-later

---

## Support

Contact [Direktt](https://direktt.com/) for questions, issues, or contributions.