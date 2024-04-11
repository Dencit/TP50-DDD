
### 表结构生成markdown文档

~~~
> 以下指令,适用于此参数结构:
[php,tool,table:*,'数据表名(全名)','指定数据库连接配置']

> 生成 指定数据表 结构
php tool table:md qj_test_childs

> 生成 指定数据表 结构 - 指定库
php tool table:md qj_test_childs database

> 生成 所有数据表 结构
php tool table:md *  

> 生成 所有数据表 结构 - 指定库
 php tool table:md * database
~~~


### YAPI导出文档(json数据) 转 POSTMAN导入文档(json数据) 
* 最大化保留"原始传参示例值".
* 统一设置 Header参数, 并转postman环境变量占位符(再手动配置postman对应的环境变量就行).
* yapi markdown文档内容, 转注释内容, 写入postman的"Test"标签.

~~~
> 以下指令,适用于此参数结构:
[php,tool,yapi:*,'markdwon根目录-yapi导出文件路径','markdwon根目录-postman文件路径']

> yapi转psm 单文件
php tool yapi:psm  yapi/api.json  postman/api.json

> yapi转psm 多文件
php tool yapi:psms  yapi/api.json  postman/api

~~~


### YAPI项目(mongodb数据) 转 POSTMAN导入文档(json数据) - 未完成

~~~
> 以下指令,适用于此参数结构:
[php,tool,yapi-db:*,'YAPI项目id','markdwon根目录-postman文件路径']

> yapi db数据 转psm 单文件
php tool yapi-db:psm  18  postman/ypdb-api.json

>yapi db数据 转psm 多文件
php tool yapi-db:psms  18  postman/ypdb-api 

~~~