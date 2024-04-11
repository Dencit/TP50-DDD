### 接口调用规范

#### 说明

```
如果是自动生成的接口代码, 且执行主表自定义查询, 都默认支持下面这些查询query.
```

###### POST 类型接口

```
没有特别规定
```

###### GET 类型接口

```
get接口若要开启临时缓存, 统一添加query参数: '_time'. 约定不传该参数时,默认调用缓存 ; 传 '_time=1' 时, 跳过缓存.
```

* 获取-列表-实时 & 获取-详情-实时:

```
{{base_url}}/demo/sample/index ?_time=1
{{base_url}}/demo/sample/read/1 ?_time=1
----------------------------------------
'_time'      |   number   |  跳过缓存: 不跳过=0,跳过=1, 默认不跳过.
```

* 获取-列表-翻页:

```
{{base_url}}/demo/sample/index ?_pagination=true &_page=1 &_page_size=3
----------------------------------------
'_pagination'   | boolean | 翻页 打开=true,关闭=false; 关闭时,一页100条数据上限; 默认20;
'_page'         | number  | 页码 默认1
'_page_size'    | number  | 页数 默认20
```

* 获取-列表-排序:

```
{{base_url}}/demo/sample/index ?_sort=-id
----------------------------------------
'_sort' | string | 自定义排序, 正序= id , 倒序= -id ; 默认倒序, id可以是其它字段;
```

* 获取-列表-筛选动作 `_search` :

```
{{base_url}}/demo/sample/index ?_search=user &type=1 &status=1,2 &name=陈%
----------------------------------------
'_search'      |  string  |  触发"user-用户端"筛选动作, 默认值对应实际api根路径名,而这里是'demo'. 若有其它筛选动作,再增加动作名称.
'type'         |  string  |  触发筛选动作时, 添加 type = 1 的筛选条件, '=,>,<,>=,<='运算符,服务端内部设定,前端不用关心. 字段名对应表字段.
'status'       |  string  |  触发筛选动作时, 添加 status in 1,2 的筛选条件, 即包含条件. 字段名对应表字段.
'name'         |  string  |  触发筛选动作时, 添加 name like 陈% 的筛选条件, 即"陈"开头的姓名. 字段名对应表字段.
```

* 获取-列表-关联副表数据:

```
{{base_url}}/demo/sample/index ?_include=image,video
----------------------------------------
'_include'      |   string   | 指定关联模型 关联 image,video 数据, 需要服务端定制;
```

* 获取-列表-副表扩展查询:

```
{{base_url}}/demo/sample/index ?_extend=user_event_have &type=1 &status=1 ...
----------------------------------------
'_extend'            |  string   | 自动扩展查询动作 user_event_have, 需要服务端定制;
'type','status',...  |  string   | 触发查询动作 user_event_have 时, 传递的查询参数, 可识别多个参数, 需要服务端定制;
```