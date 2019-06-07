how to get photobox library with composer:

in composer.json under "repositories" add
```
        {
            "type": "package",
            "package": {
                "name": "yairEO/photobox",
                "version": "1.9.12",
                "type": "drupal-library",
                "dist": {
                    "url": "https://github.com/yairEO/photobox/archive/1.9.2.zip",
                    "type": "zip"
                }
            }
        }
```

then
```composer require yairEO/photobox:1.9.12```
