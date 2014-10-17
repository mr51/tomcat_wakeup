# TOMCAT helthcheck & reboot script
----

## 概要
ページにアクセスしてtomcatが正常に動作しているか確認をして落ちている場合は再起動します。 
helthcheck 再起動プロセスにそこそこ時間がかかるため10min以上の間隔でcron実行します。

## requirement
* php5.4 以上
* wget

## cron setup

```
crontab -e
```

```
 */10 * * * * /usr/bin/php /var/source/script/tomcat_wakeup/pagecheck_and_reboot.php
```

## LISENSE
This software is released under the MIT License, see LICENSE
