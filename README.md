#queXS

An Open Source, web based, CATI system

queXS can operate your outbound telephone research centre. It integrates with Limesurvey for questionnaire design and administration, the Asterisk VoIP Server, uses AAPOR Standard Outcome codes and only requires a web browser to operate.

Unless otherwise stated in the code, the code for queXS is licenced under the GPL-v2. All included code has been checked for compatability with this licence.

Development for queXS occurs on Launchpad: https://launchpad.net/quexs

##Upgrades

If you have a previous version of queXS installed, please check the CHANGELOG file for details of how to upgrade

##Requirements (Ubuntu 16.04)

`apt-get install php mysql-server php-mysql unzip php-mbstring libphp-adodb

##Installation (from queXS 1.15.0)

```
#Download and extract queXS to your webroot
unzip quexs-1.15.0.zip -d /var/www/html
cd /var/www/html/quexs
#Create a MySQL/mariadb database 
mysqladmin create quexs
#Import the database structure from the database/quexs.sql file
mysql -uroot quexs < database/quexs.sql
#Install the timezone database 
mysql_tzinfo_to_sql /usr/share/zoneinfo | mysql -u root mysql
#Create the default config file
cp config.inc.local.php.example config.inc.local.php
```

Then browse to the queXS URL and login using the default credentials (admin/password)

