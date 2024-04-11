<?php

namespace extend\elastic;

use Elasticsearch\ClientBuilder;
use think\Config;

/*
 * https://www.cnblogs.com/jiqing9006/p/9274289.html
 */

class EsSchema
{

    private static $instance; //单例
    private static $esClient; //es单例

    private static $indexDbName; //当前库名
    private static $indexTableName; //当前表名

    private static $params; //待添加数据
    private static $settings; //库设置
    private static $option; //当前表设置
    private static $properties; //当前表字段
    private static $mappings; //表集合

    private static $changeProperties; //当前表修改字段
    private static $changeMappings; //表修改集合

    protected function __construct($settings = null, $option = null)
    {
        //节点-默认值
        //副本节点数 - 默认单节点
        if (!isset($settings['number_of_replicas'])) {
            $settings['number_of_replicas'] = 0;
        }
        //索引分片数 - 默认5个
        if (!isset($settings['number_of_shards'])) {
            $settings['number_of_shards'] = 5;
        }
        //索引刷新间隔 - 大量导入时,可设置为-1,以加快导入速度 - 默认重置为系统设置
        if (!isset($settings['refresh_interval'])) {
            $settings['refresh_interval'] = null;
        }
        //
        if (!empty($settings)) {
            self::$settings = $settings;
        }

        //索引-默认值
        //
        if (!isset($option['_all'])) {
            self::$option ['_all'] = ['enabled' => false];
        }
        //
        if (!isset($option['_source'])) {
            self::$option ['_source'] = ['enabled' => true];
        }
        //
        if (!isset($option['_routing'])) {
            self::$option ['_routing'] = ['required' => true];
        }
        //
        if (!empty($option)) {
            self::$option                = $option;
            self::$mappings["_default_"] = $option;
        }
    }

    //单例初始化
    public static function instance($settings = null, $option = null)
    {
        if (!self::$instance instanceof static) {
            self::$instance = new static($settings, $option);
        }
        return self::$instance;
    }

    //es单例初始化
    public static function esClient()
    {
        if (!self::$esClient instanceof static) {
            $hosts          = [Config::get('ELASTICE_SEARCH.HOST')];
            $client         = ClientBuilder::create()->setHosts($hosts)->build();
            self::$esClient = $client;
        }
        return self::$esClient;
    }


    //重置DSL内部常量
    protected static function resetDslConstant()
    {
        self::$instance         = null;
        self::$esClient         = null;
        self::$indexDbName      = null;
        self::$indexTableName   = null;
        self::$params           = null;
        self::$settings         = null;
        self::$properties       = null;
        self::$mappings         = null;
        self::$changeProperties = null;
    }

    /**
     * notes: 设置库
     * @author 陈鸿扬 | @date 2021/3/29 11:37
     * @param $indexDbName //索引名（相当于mysql的数据库）
     * @param $settings //设置分片数
     * @return $this
     * @deprecated - 废弃,不再区分 db/table, index/type, 一律同名.
     */
    public function database($indexDbName, $settings = null)
    {
        self::$params['index'] = self::$indexDbName = $indexDbName;

        //默认值
        //副本节点数 - 默认单节点
        if (!isset($settings['number_of_replicas'])) {
            $settings['number_of_replicas'] = 0;
        }
        //索引分片数 - 默认5个
        if (!isset($settings['number_of_shards'])) {
            $settings['number_of_shards'] = 5;
        }
        //索引刷新间隔 - 大量导入时,可设置为-1,以加快导入速度 - 默认重置为系统设置
        if (!isset($settings['refresh_interval'])) {
            $settings['refresh_interval'] = null;
        }

        if (!empty($settings)) {
            self::$settings = $settings;
        }
        return $this;
    }

    /**
     * notes: 设置库
     * @author 陈鸿扬 | @date 2021/3/29 11:37
     * @param $settings //设置分片数
     * @return $this
     */
    public function setting($settings = null)
    {
        //默认值

        //副本节点数 - 默认单节点
        if (isset($settings['number_of_replicas'])) {
            self::$settings['number_of_replicas'] = $settings['number_of_replicas'];
        }
        //索引分片数 - 默认5个
        if (isset($settings['number_of_shards'])) {
            self::$settings['number_of_shards'] = $settings['number_of_shards'];
        }
        //索引刷新间隔 - 大量导入时,可设置为-1,以加快导入速度 - 默认重置为系统设置
        if (isset($settings['refresh_interval'])) {
            self::$settings['refresh_interval'] = $settings['refresh_interval'];
        }

        return $this;
    }

    /**
     * notes: 清空库索引 - 清除index+type层 所有索引
     * @author 陈鸿扬 | @date 2021/3/29 13:12
     * @return array
     */
    public function drop()
    {

        $this->ignoreError();//是否忽略错误信息

        $client = self::esClient();
        $result = $client->indices()->delete(self::$params);

        self::$params = [];
        return $result;
    }

    /**
     * notes: 删除表索引 - 清除type层的索引
     * @author 陈鸿扬 | @date 2021/3/29 12:57
     * @return array
     */
    public function delete()
    {
        self::$params['index'] = self::$indexDbName;

        $this->ignoreError();//是否忽略错误信息

        $client = self::esClient();
        $result = $client->indices()->delete(self::$params);

        self::$params = [];
        return $result;
    }

    /**
     * notes: 设置表
     * @author 陈鸿扬 | @date 2021/3/29 11:54
     * @param $indexDbName //索引名（相当于mysql的数据库）
     * @param $indexTableName //类型名（相当于mysql的表）
     * @return $this
     */
    public function table($indexDbName, $indexTableName = null)
    {
        self::$params['index'] = self::$indexDbName = $indexDbName;

        //type不传时,与db一致
        if (empty($indexTableName)) {
            self::$indexDbName    = $indexDbName;
            self::$indexTableName = $indexDbName;
        } else {
            self::$indexDbName    = $indexDbName;
            self::$indexTableName = $indexTableName;
        }

        return $this;
    }


    /**
     * notes: 添加表字段
     * @author 陈鸿扬 | @date 2021/3/29 12:08
     * @param $columnName
     * @param $option
     * type:byte,short,integer,long,float,double,boolean,date,binary,ip,token_count;
     * index:analyzed,not_analyzed,no;
     * store:true,false;
     * @return $this
     */
    public function addColumn($columnName, $option)
    {
        //日期格式默认处理
        if (isset($option['type']) && $option['type'] == 'date' && !isset($option['format'])) {
            $option['format'] = 'yyyy-MM-dd HH:mm:ss||yyyy-MM-dd||epoch_millis';
        }
        //整型变量 - 使用精确索引
        if (isset($option['type']) && $option['type'] == 'integer') {
            $option['index'] = true;
        }
        //整型变量 - 使用精确索引
        if (isset($option['type']) && $option['type'] == 'long') {
            $option['index'] = true;
        }
        //字符串变量 - 打开聚合计算
        if (isset($option['type']) && $option['type'] == 'text') {
            $option['index']     = true;
            $option['fielddata'] = true;
        }

        self::$properties["$columnName"]                     = $option;
        self::$mappings[self::$indexTableName]['properties'] = self::$properties;
        return $this;
    }

    /**
     * notes: 修改表字段
     * @param $columnName
     * @param $option
     * @return $this
     * @author 陈鸿扬 | @date 2022/6/9 19:29
     */
    public function changeColumn($columnName, $option)
    {
        //日期格式默认处理
        if (isset($option['type']) && $option['type'] == 'date' && !isset($option['format'])) {
            $option['format'] = 'yyyy-MM-dd HH:mm:ss||yyyy-MM-dd||epoch_millis';
        }

        self::$changeProperties["$columnName"] = $option;
        self::$changeMappings['properties']    = self::$changeProperties;
        return $this;
    }


    /**
     * notes: 参数预览
     * @author 陈鸿扬 | @date 2021/3/29 12:15
     * @return mixed
     */
    public function getParams()
    {
        self::$params['index'] = self::$indexDbName;
        if (count($this->combineBody()) > 0) {
            self::$params['body'] = $this->combineBody();
        }
        return self::$params;
    }

    /**
     * notes: 创建库索引
     * @author 陈鸿扬 | @date 2021/3/29 12:14
     * @return array
     */
    public function create()
    {
        $client                = self::esClient();
        self::$params['index'] = self::$indexDbName;
        self::$params['body']  = $this->combineBody();

        $result = $client->indices()->create(self::$params);

        //重置DSL内部常量 - 已便开始新的操作时不受到污染
        self::resetDslConstant();

        return $result;
    }

    /**
     * notes: 修改库索引 - 无法更新非动态设置
     * @return array
     * @author 陈鸿扬 | @date 2022/6/9 14:21
     */
    public function update()
    {
        $client                = self::esClient();
        self::$params['index'] = self::$indexDbName;

        self::$params['body'] = $this->changeCombineBody();
        //dd(self::$params);
        $result = $client->indices()->putSettings(self::$params);

        //重置DSL内部常量 - 已便开始新的操作时不受到污染
        self::resetDslConstant();

        return $result;
    }

    /**
     * notes: 合并body
     * @author 陈鸿扬 | @date 2021/3/29 14:45
     * @return mixed
     */
    public function combineBody()
    {
        $body = [];
        if (!empty(self::$settings)) {
            $body['settings'] = self::$settings;
        }
        if (!empty(self::$mappings)) {
            $body['mappings'] = self::$mappings;
        }
        return $body;
    }

    /**
     * notes: 修改索引-合并body
     * @return array
     * @author 陈鸿扬 | @date 2022/6/9 15:27
     */
    public function changeCombineBody()
    {
        $body = [];
        if (!empty(self::$option)) {
            $body = self::$option;
        }
        //
        $currProperties = [];
        if (!empty(self::$properties)) {
            $currProperties = array_merge($currProperties, self::$properties);
        }
        if (!empty(self::$changeProperties)) {
            $currProperties = array_merge($currProperties, self::$changeProperties);
        }
        $body['mappings']['properties'] = $currProperties;
        //
        if (!empty(self::$settings)) {
            $body['settings'] = self::$settings;
        }
        return $body;
    }

    /**
     * notes: 检测库是否存在
     * @author 陈鸿扬 | @date 2021/3/29 12:35
     * @param $indexDbName //当前库名
     * @return bool
     */
    public function exists($indexDbName)
    {
        $this->ignoreError();//是否忽略错误信息
        self::$params['index'] = $indexDbName;
        $client                = self::esClient();
        $result                = $client->indices()->exists(self::$params);
        return $result;
    }

    /**
     * notes: 获取索引结构
     * @param $indexDbName
     * @param array $fieldTemple - 返回 带默认值结构
     * @return array - 返回 key结构
     * @author 陈鸿扬 | @date 2022/12/6 14:54
     */
    public function attributes($indexDbName, &$fieldTemple = [])
    {
        $this->ignoreError();//是否忽略错误信息
        self::$params['index'] = $indexDbName;
        $client                = self::esClient();
        $result                = $client->indices()->getMapping(self::$params);

        $fields = [];
        if (!empty($result)) {
            $properties = $result["$indexDbName"]["mappings"]["$indexDbName"]["properties"];
            $fields     = array_keys($properties);
            array_walk($properties, function ($item, $key) use (&$fieldTemple) {
                $value = '';
                switch ($item["type"]) {
                    case "long":
                    case "integer":
                        $value = 0;
                        break;
                    case "text":
                        $value = "";
                        break;
                    case "date":
                        $value = null;
                        break;
                }
                $fieldTemple["$key"] = $value;
            });
        }

        return $fields;
    }

    /**
     * notes: 获取库索引设置信息
     * @author 陈鸿扬 | @date 2021/3/29 12:55
     * @param $indexDbName //当前库名
     * @return array
     */
    public function getSettings($indexDbName)
    {
        $this->ignoreError();//是否忽略错误信息
        self::$params['index'] = $indexDbName;
        $client                = self::esClient();
        $result                = $client->indices()->getSettings(self::$params);
        return $result;
    }

    /**
     * notes: 获取mapping信息
     * @author 陈鸿扬 | @date 2021/3/29 13:02
     * @param $indexDbName //当前库名
     * @return array
     */
    public function getMapping($indexDbName)
    {
        $this->ignoreError();//是否忽略错误信息
        self::$params['index'] = $indexDbName;
        $client                = self::esClient();
        $result                = $client->indices()->getMapping(self::$params);
        return $result;
    }

    //忽略错误信息
    protected function ignoreError()
    {
        self::$params['client']['ignore'] = 404;
    }

}
