# zohocreator-api

## About

This is a PHP class to consume Zoho Creator's API.

https://www.zoho.com/creator/

## Usage

Here are the class's public methods. For more information on usage, please visit Zoho Creator's website.

```php
// Initializes the class.
$zoho = new Zoho_Creator_API($login_id, $password, $api_key, $application_name);

// Initializes an API ticket.
$zoho->init_api_ticket();

// Kills an API ticket.
$zoho->kill_api_ticket();

// Adds an entry.
$zoho->add($form_name, $data_array);

// Updates an entry.
$zoho->update($form_name, $data_array, $criteria, $reloperator);

// Updates if exists, adds if not.
$zoho->update_else_add($form_name, $data_array), $criteria);
```
