#### SAMPLE-[TABLE]

> samples | 模板表

| 字段 | 类型 | 必须 | 默认值 | 说明 |
| --- | --- | --- | --- | --- |
| id | int 20 | yes | auto_increment | 主键ID |
| name | string 50 | yes |  | 用户昵称 |
| nick_name | string 50 | yes |  | 用户昵称 |
| mobile | string 30 | yes |  | 绑定手机 |
| photo | string 200 | yes |  | 用户头像 |
| sex | int 3 | yes | 0 | 性别: 0未知, 1男, 2女 |
| type | int 3 | yes | 0 | 类型: 0未知, 1-否, 2-是 |
| status | int 3 | yes | 0 | 状态: 1-否, 2-是 |
| create_time | date_time | yes |  | 创建时间 |
| update_time | date_time | no |  | 更新时间 |
| delete_time | date_time | no |  | 删除时间 |
