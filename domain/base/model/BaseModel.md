# 模型基础类 - 扩展方法说明
---

> 1 后置关联预载入
~~~
//模型方法例子
class ResumeIntentionModel extends BaseModel
{
    ...

    //意向行业1级 - 后置关联预载入
    public function af_top_trade($collectObj)
    {
        $fields='id,parentid,prefix,name';

        //桥接关系-关键字段
        $startKey = 'tradeid';
        $leftKey = 'id'; $rightKey = 'prefix';
        $endKey = 'id';

        //意向行业3级
        $CityModel = PositionTradeModel::field([$leftKey, $rightKey]);
        //意向行业2级
        $ProvModel = PositionTradeModel::field($fields);

        //中间表-右联字段-处理闭包
        $rightKeyWalk = function ($item, &$currRightKey) use ($rightKey) {
            $currRightKey = $rightKey;
            $prefix       = $item[$rightKey];
            $prefixArr = explode('_',$prefix); //截取分隔符数据中的 2级id
            return $prefixArr[0] ?? null;
        };

        //后置关联预载入
        $this->afterWithInit($collectObj, 'top_trade')
            ->afLongsToOne($CityModel, $ProvModel, $startKey, $leftKey, $rightKeyWalk, $endKey);
            //->afLongsToMany($CityModel, $ProvModel, $startKey, $leftKey, $rightKeyWalk, $endKey)
            ;

        return $this;
    }

    ...
}

//调用方式

        $result = ResumeIntentionModel::field([*])->find();
        if (!empty($result)) {
            //执行查询之后才有效
            $result->afterWith(['top_trade']); //自动加载 model 内 "af_" 开头的同名方法
        }
        //获取关联预载入结果
        $resultArr = $result->toArray();
    

~~~