####SAMPLE-[UPDATE]

###### URL

~~~
PUT : {{base_url}}/demo/sample/{id}

{id} : 236
~~~

###### QUERY

~~~
~~~

###### HEADER

~~~
token : 
~~~

###### BODY

~~~
name : update更新
nick_name : 
mobile : 
photo : 
sex : 0
type : 0
status : 0
~~~

###### BODY_DESC

| 字段 | 类型 | 必须 | 默认值 | 说明 |
| --- | --- | --- | --- | --- |
| name | string 50 | yes |  | 用户昵称 |
| nick_name | string 50 | yes |  | 用户昵称 |
| mobile | string 30 | yes |  | 绑定手机 |
| photo | string 200 | yes |  | 用户头像 |
| sex | int 3 | yes | 0 | 性别: 0未知, 1男, 2女 |
| type | int 3 | yes | 0 | 类型: 0未知, 1-否, 2-是 |
| status | int 3 | yes | 0 | 状态: 1-否, 2-是 |

###### RESPONSE

~~~
{"data":{"name":"update更新","nick_name":"","mobile":"","photo":"","sex":0,"type":0,"status":0,"id":"236","update_time":"2021-04-12 19:37:47"}}
~~~

