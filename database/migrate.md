#说明
https://book.cakephp.org/phinx/0/en/index.html
https://www.jianshu.com/p/7d3ab62c0476
https://www.kancloud.cn/manual/thinkphp5/235127
https://blog.csdn.net/gaosf123/article/details/80618315

#建表准备
php think migrate:create Demos

#表字段补充
php think migrate:create AddColumnToDemos

#执行
php think migrate:run

#回滚
php think migrate:rollback