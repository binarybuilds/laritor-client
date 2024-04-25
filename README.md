CronBuzz PHP
------------------------------------------------
This is the official php client for cronbuzz API. Use this client to manage your cronbuzz monitors or send pings from your cron jobs easily.

### Installation

This package can be installed using composer.
```bash
composer require binarybuilds/cronbuzz-php
```

### Sending Pings from your cron jobs

Use the following code to automatically send proper pings to cronbuzz.

```php
\BinaryBuilds\CronBuzzPHP\CronBuzzTask::run('your-monitor-uuid', function (){

    // Add your code here
        
});
```

The above code will
- send a ping to cronbuzz informing the cron job execution has started
- wraps your code inside a `try` `catch` block
- sends a ping when your code completes executing.
- If any exception occurred during the execution of your code, `catch` block will catch the execution, Send a ping to cronbuzz informing the cron job failed executing. It will also include the error message.
- Re-throws the exception, So you can handle the exceptions as usually.

---
If for any reason the above code does not work for you,
Or if you prefer to send pings manually, Use the below code.

```php

$run = new \BinaryBuilds\CronBuzzPHP\Run( 'your-monitor-uuid' );
$run->start();

try{
   
    // Add your code here

    $run->complete();

}catch (\Exception $exception){

    $run->fail( $exception->getMessage() );
    
    // Handle your exceptions here    
}

```

## Using API

### Authorization

Before you make any requests to the API,
You must authenticate using your API key.

> This step is not required for sending pings from your cron jobs.

Add the below lines at the beginning of your code.

```php
\BinaryBuilds\CronBuzzPHP\CronBuzzAPI::setTeamKey('your-team-key');
\BinaryBuilds\CronBuzzPHP\CronBuzzAPI::setApiKey('your-api-token');
```

You can retrieve your team key from the **team settings** page, and
your api token from the **profile** page.

### Monitors

#### List Monitors
```php
\BinaryBuilds\CronBuzzPHP\Monitor::list();
```

#### Show Monitor
```php
\BinaryBuilds\CronBuzzPHP\Monitor::get( 'monitor id');
```

#### Create Monitor
```php
\BinaryBuilds\CronBuzzPHP\Monitor::create(
    'monitor name', 
    'schedule', 
    'max execution', 
    'notification lists', 
    'tags'
);
```
| Argument | Data Type | Required | Details |
|:----:|:----:|:----:|:----:|
| Monitor name | string | Yes | Name of the monitor |
| Schedule | string | Yes | monitor cron expression(ex: * * * * *) |
| max execution | integer | Yes | Maximum execution duration of the monitor in minutes |
| notification lists | array | Optional | Array of notification list names to attach to this monitor. If none specified, Default notification list will be attached. |
| tags | array | Optional | Array of tags to add to this monitor. |

#### Update Monitor
```php
\BinaryBuilds\CronBuzzPHP\Monitor::update( 'monitor id', 'fields');
```
| Argument | Data Type | Required | Details |
|:----:|:----:|:----:|:----:|
| Monitor id | integer | Yes | id of the monitor(This is not the uuid.) |
| fields | array | Yes | array of fields to update(ex: `['name' => 'new name', 'execution_duration' => 2]`. Array keys must be one of these `name`,`schedule`,`execution_duration`) |

#### Delete Monitor
```php
\BinaryBuilds\CronBuzzPHP\Monitor::delete( 'monitor id');
```

#### Pause Monitor
```php
\BinaryBuilds\CronBuzzPHP\Monitor::pause( 'monitor id');
```

#### Resume Monitor
```php
\BinaryBuilds\CronBuzzPHP\Monitor::resume( 'monitor id');
```

### Notification Lists

#### List Notification Lists
```php
\BinaryBuilds\CronBuzzPHP\NotificationList::list();
```

#### Show Notification List
```php
\BinaryBuilds\CronBuzzPHP\NotificationList::get( 'list id');
```

#### Create Notification List
```php
\BinaryBuilds\CronBuzzPHP\NotificationList::create( 'list name', 'channels');
```
| Argument | Data Type | Required | Details |
|:----:|:----:|:----:|:----:|
| list name | string | Yes | Name of the notification list |
| channels | array | Optional | Array of notification channels to add to this list. |

##### Notification channels format
```php
[
    [ 'type' => 'EMAIL', 'yourname@yourcompany.com'],
    [ 'type' => 'WEBHOOK', 'https://your-webhook-url.com/'],
]
```

#### Update Notification List
```php
\BinaryBuilds\CronBuzzPHP\NotificationList::update( 'list id', 'new name');
```

#### Delete Notification List
```php
\BinaryBuilds\CronBuzzPHP\NotificationList::delete( 'list id');
```

### Tags

#### List Tags
```php
\BinaryBuilds\CronBuzzPHP\Tag::list();
```

#### Show Tag
```php
\BinaryBuilds\CronBuzzPHP\Tag::get( 'tag id');
```

#### Create Tag
```php
\BinaryBuilds\CronBuzzPHP\Tag::create( 'tag name');
```

#### Update Tag
```php
\BinaryBuilds\CronBuzzPHP\Tag::update( 'tag id', 'new name');
```

#### Delete Tag
```php
\BinaryBuilds\CronBuzzPHP\Tag::delete( 'tag id');
```

## Security Vulnerabilities

If you found a security vulnerability with in this package, Please do not use the issue tracker. Instead send an email to `support@cronbuzz.com`. All security vulnerabilities will be addressed promptly.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE.md).
