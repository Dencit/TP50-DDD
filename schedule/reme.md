### 基础命令目录

>Timer.php - 独立调度控制台 - 不依赖框架Command注册
~~~
supervisor配置文件: tp50_timer.ini

[program:tp50_timer]
directory = /alidata/www/tp50/
command = php72 timer queue start
redirect_stderr = true
stdout_logfile= /alidata/log/supervisor/tp50/tp50_timer.log

user = apache
autostart = true
auturestart = true

startsecs = 10
stopwaitsecs = 10
priority = 1
stopasgroup = true
killasgroup = true

~~~
~~~
supervisor 执行命令:

更新配置: supervisorctl update
获取守护状态: supervisorctl status tp50_timer
停止守护状态: supervisorctl stop tp50_timer

清除easyTask孤儿进程: ps -ef|grep tp50_timer|awk '{print $2}'|xargs kill -9
清除thinkphp队列进程: ps -ef|grep php72|grep tp50|grep think|grep queue|awk '{print $2}'|xargs kill -9
清除thinkphp命令进程: ps -ef|grep php72|grep tp50|grep think|awk '{print $2}'|xargs kill -9

开启守护状态: supervisorctl start zhanchi_timer

~~~

>CronTimer.php - cron-expression 基本定时任务
~~~
crontab 配置文件: /var/spool/cron/root

# 调度器 - 每分钟 执行
* * * * * cd /alidata/www/tp50/ && sudo -u apache /usr/local/bin/php72 think cron_timer

~~~

>QueueMonitor.php - 队列监控
~~~
crontab 配置文件: /var/spool/cron/root

# 队列监控 - 每分钟 执行
* * * * * sudo -u apache /usr/local/bin/php72 /alidata/www/tp50/think queue_monitor

~~~