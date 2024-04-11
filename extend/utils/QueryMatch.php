<?php

namespace extend\utils;

use Domain\Base\Exception\Exception;

/**
 * notes: query查询表达式参数 获取工具
 */
class QueryMatch
{
    protected static $instance = null;

    protected $requestQuery = [];

    public function __construct(array $requestQuery)
    {
        $this->requestQuery = $requestQuery;
    }

    //单例
    public static function instance(array $requestQuery)
    {
        if (!(self::$instance instanceof static)) {
            self::$instance = new static($requestQuery);
        }
        return self::$instance;
    }

    //获取指定 query key
    public function getQuery($key, $default = null)
    {
        if (isset($this->requestQuery["$key"])) {
            return $this->requestQuery["$key"];
        }
        return $default;
    }

    //query ?where=key/value 运算符转换
    protected function operator(&$item)
    {
        preg_match('/^[\w\s]+(>\\/|<\\/|>|<|\\/|\\|).*$/i', $item, $m);
        //var_dump($m);die;//
        if (isset($m[1])) {
            $item = explode($m[1], $item);
        }
        if (is_array($item)) {
            $item[2] = $item[1]; //拷贝值到新位置,原位置准备存放运算符
            switch ($m[1]) { //当匹配到 运算符标记时
                default:
                    $item[1] = '=';
                    break;
                case "/":
                    $item[1] = '=';
                    break;
                case ">":
                    $item[1] = '>';
                    break;
                case "<":
                    $item[1] = '<';
                    break;
                case ">/":
                    $item[1] = '>=';
                    break;
                case "</":
                    $item[1] = '<=';
                    break;
                case "|":
                    $item[1] = 'like';
                    preg_match('/^\%/i', $item[1], $left);
                    preg_match('/\%$/i', $item[1], $right);
                    $item[2] = trim($item[2], '%');
                    if (isset($left[0]) && $left[0] == '%') {
                        $item[2] = '%%' . $item[2];
                    } else if (isset($right[0]) && $right[0] == '%') {
                        $item[2] = $item[2] . '%%';
                    } else {
                        $item[2] = '%%' . $item[2] . '%%';
                    }
                    //var_dump($item[1]);die;//
                    break;
            }
        }

        //检查 where表达式 是否合法
        if (!is_array($item)) {
            AppException::throwLog(Code::toolMsg[2000],2000,__METHOD__);
        }

        //var_dump($item);die;//
        return $item;
    }

    //query ?where_in=key/value,value,.. 运算符转换
    protected function inOperator(&$item, &$sortItem = null)
    {
        preg_match('/^([\w\s]+)(\\/)(.*)$/i', $item, $m);
        //var_dump($m);die;//
        if (isset($m[1])) {
            $item = explode($m[1], $item);
        }
        if (is_array($item)) {
            $values = explode(',', $m[3]);
            $values = array_unique($values);
            $whereInArr = [$m[1], 'in', implode(',', $values)];
            $item = $whereInArr;
            //返回排序结构
            $sortItem[0] = $m[1];
            $sortItem[1] = implode(',', $values);
        }

        //检查 where_in表达式 是否合法
        if (!is_array($item)) {
            AppException::throwLog(Code::toolMsg[2001],2001,__METHOD__);
        }

        return $item;
    }


    //?where=status/0
    public function where(&$whereArr, $kv = false)
    {
        $where = $this->getQuery('_where');
        if ($where) {
            $where = explode(',', $where);
            if (is_array($where)) {
                foreach ($where as $item) {
                    $this->operator($item);//传的引用
                    if ($kv) {
                        $whereArr[$item[0]] = $item[2];
                    } else {
                        $whereArr[] = $item;
                    }
                }
                return $whereArr;
            }
        }
    }

    /**
     * notes: 获取单个查询动作
     * @param string $action
     * @return mixed|string|null
     * @author 陈鸿扬 | @date 2022/4/4 17:37
     */
    public function searchAction(&$action='default')
    {
        $action =  $this->getQuery('_search','default');
        return $action;
    }

    /**
     * notes: 捕捉 ?type=1 & status=1 ... 的值, 转化成查询数组
     * @param array $searchArr - 间接返回查询结构数组 到 外部引用值
     * @param array $rule - 设置字段对应运算符: ["key_name"=>'>=']
     * @param array $filterArr - 指定过滤字段: ['type','status']
     * @param string $filter - 指定过滤类型: only-提取|except-排除
     * @return array - 直接返回查询结构数组
     * @author 陈鸿扬 | @date 2022/4/4 17:31
     */
    public function search(&$searchArr = [], $rule = [], $filterArr = [], $filter = 'only')
    {
        //排除参数集
        $outQuery = [
            'pagination', 'page', 'page_size', 'per_page',
            '_pagination', '_page', '_page_size', '_per_page',
            '_where', '_where_in', '_where_in_sort', '_include', '_extend', '_search',
            '_sort', '_time'
        ];
        //过滤排除参数
        $query = $this->requestQuery;
        $queryArr = array_diff_key($query, array_flip($outQuery));
        //提取指定字段 $filterArr=['type','status']
        if (!empty($filterArr) && $filter == 'only') {
            $queryArr = array_intersect_key($queryArr, array_flip($filterArr));
        }
        //排除指定字段 $filterArr=['type','status']
        if (!empty($filterArr) && $filter == 'except') {
            $queryArr = array_diff_key($queryArr, array_flip($filterArr));
        }
        //附加运算符规则转化 $rule = ["key_name"=>'>='];
        if (!empty($queryArr)) {
            array_walk($queryArr, function ($value, $keyName) use ($rule, &$searchArr, &$searchKeyArr) {
                if (!empty($rule["$keyName"])) {
                    $operator = $rule["$keyName"];
                } else {
                    $operator = '=';
                }
                //筛选运算符预处理
                $this->searchOperator($operator,$value);
                //值非空字符串才获取
                if( $value !== '' ){
                    $currArr = ["$keyName", $operator, $value];
                    $searchArr[] = $currArr;
                }
            });
        }
        return $searchArr;
    }

    //筛选运算符预处理
    protected function searchOperator(&$operator, &$value)
    {
        switch ($operator) {
            case 'like': //模糊筛选处理
                preg_match('/^\%|\*/i', $value, $m);
                if ( !isset($m[0]) ) {
                    $value = '%' . $value . '%';
                }
                break;
            case '=': //兼容多选-逗号分隔
                preg_match('/\,|\%|\*/i', $value, $m);
                if (isset($m[0]) && $m[0] == ',') {
                    $operator = 'in';
                }
                if (isset($m[0]) && $m[0] == '%') {
                    $operator = 'like';
                }
                if (isset($m[0]) && $m[0] == '*') {
                    $operator = 'like';
                    $value = preg_replace('/^\*(.*)\*$/i',"%$1%",$value);
                }
                break;
        }
    }

    //获取 where表达式中类似这样的,同字段筛选范围: statistics_date >= Y-m-d H:i:s; statistics_date<= Y-m-d H:i:s;
    public function getStartEndForWhere($whereArr, $key, &$startDate, &$endDate)
    {
        $startDate = '';
        $endDate = '';
        if ($whereArr && count($whereArr) > 0) {
            foreach ($whereArr as $ind => $item) {
                if ($item[0] == $key && $item[1] == '>=') {
                    $startDate = $item[2];
                };
                if ($item[0] == $key && $item[1] == '<=') {
                    $endDate = $item[2];
                };
            }
        }
    }

    //按 where 筛选 - 闭包
    public function whereClosure($whereArr, \Closure $closure)
    {
        if (!empty($whereArr)) {
            foreach ($whereArr as $ind => $data) {
                $closure($data);
            }
        }
    }

    //按 where_in_ id 筛选 - 闭包
    public function whereInClosure($whereInArr, \Closure $closure)
    {
        foreach ($whereInArr as $ind => $data) {
            $closure($data);
        }
    }

    //?where_in=status/1,2,3
    public function whereIn(&$whereInArr, &$sortItemArr = null)
    {
        $whereIn = $this->getQuery('where_in');
        if ($whereIn) {
            $whereIn = explode('|', $whereIn);
            if (is_array($whereIn)) {
                $whereInArr = [];
                $sortItemArr = [];
                foreach ($whereIn as $item) {
                    $this->inOperator($item, $sortItem);//传的引用
                    $whereInArr[] = $item;
                    $sortItemArr[] = $sortItem;
                }
                return $whereInArr;
            }
        }
    }

    //按 where_insort id 筛选+排序 - 闭包
    public function whereInSortClosure($whereInSortArr, $sortItem = [], \Closure $closure)
    {
        if (!empty($whereInSortArr)) {
            foreach ($whereInSortArr as $ind => $data) {
                $rawStr = '';
                if (isset($sortItem[$ind])) {
                    $sortData = $sortItem[$ind];
                    $rawStr = "FIND_IN_SET(" . $sortData[0] . ",'" . $sortData[1] . "'" . ')';
                }
                $closure($data, $rawStr);
            }
        }
    }

    //?where_insort=status/1,2,3 //按id顺序返回结果
    public function whereInSort(&$whereInArr, &$sortItemArr = null)
    {
        $whereIn = $this->getQuery('_where_in_sort');
        if ($whereIn) {
            $whereIn = explode('|', $whereIn);
            if (is_array($whereIn)) {
                $whereInArr = [];
                $sortItemArr = [];
                foreach ($whereIn as $item) {
                    $this->inOperator($item, $sortItem);//传的引用
                    $whereInArr[] = $item;
                    $sortItemArr[] = $sortItem;
                }
                return $whereInArr;
            }
        }
    }

    //排序-可批量 - 默认order id=desc 排序
    public function order(&$sortArr)
    {
        $order = $this->getQuery('_sort', '-id');
        if (!empty($order)) {
            $orders = $this->sortOperator($order);
            $sortArr = $orders;
        }
    }

    //成组 - 可批量 - group by
    public function group(&$groupArr){
        $group = $this->getQuery('_group');
        if(!empty($group)){
            $groups = $this->groupOperator($group);
            $groupArr = $groups;
        }
    }
    //成组 - group参数转换
    protected function groupOperator($groupStr){
        $groupMap = explode(',',$groupStr); $groupArr=[];
        foreach ($groupMap as $ind=>$gStr){
            $groupArr[]=$gStr;
        }
        return $groupArr;
    }

    //排序批量处理 - 闭包
    public function sortClosure($sortArr, \Closure $closure)
    {
        if (!empty($sortArr)) {
            foreach ($sortArr as $k => $v) {
                $closure($k, $v);
            }
        }
    }

    //排序-可批量 - 无默认order id=desc 排序
    public function sort(&$sortArr)
    {
        $order = $this->getQuery('_sort');
        if (!empty($order)) {
            $orders = $this->sortOperator($order);
            $sortArr = $orders;
        }
    }

    //排序-sort参数转换
    protected function sortOperator($orderStr)
    {
        $sortMap = explode(',', $orderStr);
        $sortArr = [];
        foreach ($sortMap as $ind => $sortStr) {
            $orderFields = 'id';
            $orderType = 'desc';
            preg_match("/^(-|)(.*)$/i", $sortStr, $m);
            //var_dump($m);die;//
            if ($m[0]) {
                switch ($m[1]) {
                    default:
                        $orderType = 'asc';
                        break;
                    case "-" :
                        $orderType = 'desc';
                        break;
                }
                $orderFields = $m[2];
            }
            $sortArr[$orderFields] = $orderType;
        }
        return $sortArr;
    }


    //自定义关联查询范围
    public function include(&$includeArr, $classPath = null, $except = null)
    {
        $include = $this->getQuery('_include');
        if (isset($include) && !empty($include)) {
            $joins = explode(',', $include);
            $includeArr = $joins;
        } else {
            $includeArr = [];
        }

        //排除本地模型没有的关联函数
        if (!empty($includeArr) && !empty($classPath)) {
            foreach ($includeArr as $ind => $name) {
                $methodName = $this->toHumpName($name);
                $methodExits = method_exists($classPath, $methodName);
                if (!$methodExits) {
                    unset( $includeArr[$ind] );
                }
            }
        }

        //排除关联模型 - 改为后置关联模型去执行
        if( !empty($includeArr) && !empty($except) ){
            $includeArr = array_diff_assoc($includeArr,$except);
            $tempArr=[];
            array_walk($includeArr,function ($name)use(&$tempArr,$except){
                if( !in_array($name,$except) ){
                    $tempArr[]=$name;
                }
            });
            $includeArr = $tempArr;
        }
    }

    //检查关联查询模型 - 闭包
    public function incModelHaveClosure($class, $includeArr, \Closure $closure)
    {
        if(!empty($includeArr)) {
            foreach ($includeArr as $ind => $name) {
                $methodName = $this->toHumpName($name);
                $methodExits = method_exists($class, $methodName);
                if ($methodExits) {
                    $closure($methodName);
                }
            }
        }
    }

    //检查关联查询模型
    public function incModelHave(&$joins)
    {
        foreach ($joins as $ind => $name) {
            $methodName = $this->toHumpName($name);
            $methodExits = method_exists($this, $methodName);
            if (!$methodExits) {
                unset($joins[$ind]);
            };
        }
    }

    //小写名称转驼峰 - 如 user_name : userName
    public function toHumpName($name)
    {
        $nameArr = explode('_', $name);
        $newName = '';
        foreach ($nameArr as $ind => $str) {
            if ($ind == 0) {
                $newName .= strtolower($str);
            } else {
                $newName .= ucwords($str);
            }
        }
        return $newName;
    }


    /*
     * 翻页查询 scope
     */
    public function pagination(&$per_page = 20, &$page = 1, &$pagination = false, &$row = 0)
    {
        $per_page = config('paginate.page_size_default',20);
        $page = 1;
        $pagination = $this->getQuery("_pagination");
        if(!$pagination){
            $pagination = $this->getQuery("pagination");
        }


        if ($pagination != 'false') {
            self::pageParamFormat($per_page,$page,true);
        } else {
            self::pageParamFormat($per_page,$page,false);
        }

        if ($page < 1) {$page = 1;};
        $row = ($page - 1) * $per_page;

        return [
            'pagination' => $pagination,
            'per_page' => $per_page,
            'page' => $page,
            'row' => $row,
        ];
    }

    //翻页参数格式化
    public function pageParamFormat(&$per_page, &$page, $pageAble = true)
    {
        if ($pageAble) {

            //默认打开翻页 - 除非 ?_pagination = false
            if ($this->getQuery('_page_size')) {
                $per_page = (int)$this->getQuery('_page_size', 20);
            } else if ($this->getQuery('page_size')) {
                $per_page = (int)$this->getQuery('page_size', 20);
            }

            if ($this->getQuery('_per_page')) {
                $per_page = (int)$this->getQuery('_per_page', 20);
            } else if ($this->getQuery('per_page')) {
                $per_page = (int)$this->getQuery('per_page', 20);
            }

            if ($this->getQuery('_page')) {
                $page = (int)$this->getQuery('_page', 1);
            }else if ($this->getQuery('page')) {
                $page = (int)$this->getQuery('page', 1);
            }

        } else {

            //如果关闭翻页 最大翻页条数 上限到100
            $per_page = (int) 100;
            if ($this->getQuery('_page')) {
                $page = (int)$this->getQuery('_page', 1);
            }else if ($this->getQuery('page')) {
                $page = (int)$this->getQuery('page', 1);
            }

        }

    }

}