# LeDoc

LeDoc is short for Le Document, as a cooperating document system based on Pure PHP and File System.
Support Markdown. 

LeDoc 是一个超轻量级的企业协作文档系统，只需要支持PHP的服务器和操作系统的文件系统，就可以完成任务。
支持Markdown语法。

## Deploy

1. Put the project to server
1. Install PHP libraries with composer
1. Create a new directory `runtime` in project directory and make it writable
1. If use Apache 2, put `.htaccess` in project directory; or Nginx, write in site config file; to make all request to `index.php`

All is OK now.

P.S.

The content of `.htaccess` :

```apacheconfig
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
``` 

And the Nginx config sample：

```nginx
server {
    location / {
        try_files $uri $uri/ /index.php?$args;
    }
}
```
