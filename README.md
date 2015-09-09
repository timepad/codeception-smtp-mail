### Installation

Add this to composer.json

``` bash
$ composer require ahmedsamy/codeception-smtp-mail
```

### configuration
in `acceptance.yml`
``` yaml
modules:
    enabled:
        - Gmail
    config:
        Gmail:
            username: name@email.com
            password: password

```

Build codeception

``` bash
$ bin/codecept build
```

### Available methods
- ``` php seeInEmailBy() ```
- ``` php canSeeInEmailBy() ```
- ``` php seeInEmailBy() ```
- ``` php cantSeeInEmailBy() ```
- ``` php dontSeeInEmailBy() ```
- ``` php grabEmailBy() ```

###TODO

- Write tests
- Handle attachments
- Asserts links in email body
- Click links in email body
- Implement wait method
