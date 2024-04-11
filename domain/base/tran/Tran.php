<?php
namespace domain\base\tran;

class Tran
{
    /*
     * notes: 关联模型 belongsTo 的转化器
     * @author 陈鸿扬 | @date 2020/12/11 12:33
     */
    public function includeBelongsTo($includeSign, $objResult, $fields, $transformerClass, $force = false, $recursionIncludePrefix = null)
    {
        $includes = request()->get('_include');
        $includeArr = explode(',',$includes);

        if( in_array($includeSign,$includeArr) && !empty($objResult) ){
            //关联到才执行
            $model = $objResult->{$includeSign};
            if(!empty($model)) {

                if ($fields != '*') {
                    $model->visible([$includeSign => $fields]);
                }
                $transformer = new $transformerClass();

                if (empty($recursionIncludePrefix)) {
                    return $transformer->transform($model);
                } else {
                    return $transformer->transform($model, $recursionIncludePrefix);
                }

            }
        }

        return false;
    }

    /*
     * notes: 关联模型 hasMany 的转化器
     * @author 陈鸿扬 | @date 2020/12/11 12:35
     */
    public function includeHasMany($includeSign, $objResult, $fields, $transformerClass, $force = false, $recursionIncludePrefix = null)
    {

        $includes = request()->get('_include');
        $includeArr = explode(',',$includes);

        if( in_array($includeSign,$includeArr) && !empty($objResult) ){
            $objects = [];
            foreach ($objResult as $model){
                //关联到才执行
                $model = $model->{$includeSign};
                if(!empty($model)) {

                    if ($fields != '*') {
                        $model->visible([$includeSign => $fields]);
                    }
                    $transformer = new $transformerClass();

                    if (empty($recursionIncludePrefix)) {
                        $objects[] = $transformer->transform($model);
                    } else {
                        $objects[] = $transformer->transform($model, $recursionIncludePrefix);
                    }

                }
            }

            if(empty($objects)){ return false; }
            return $objects;
        }

        return false;
    }

    public function dataAfterPush(&$data,$pos,$addArr){
        if(is_string($pos)){ $kayIndexArr= array_flip(array_keys($data)); $pos = $kayIndexArr[$pos]+1; }
        $startArr = array_slice($data,0,$pos);
        $startArr = array_merge($startArr,$addArr);
        $endArr = array_slice($data,$pos);
        $data = array_merge($startArr,$endArr);
        return $data;
    }

    public function arrayFilter( $resultArr, $data ){
        $keys = array_keys($resultArr);
        foreach ( $data as $k=>$v ){

            /*if( is_string($v) ){
                preg_match('/^[0-9]+\\.[0-9]+$/is',$v,$float);
                if( isset($float[0]) ){ $data[$k]= (float)$v; }
                preg_match('/^[0-9]+$/is',$v,$int);
                if( isset($int[0]) ){ $data[$k]= (int)$v; }
            }*/

            if( !in_array($k,$keys) ){ unset($data[$k]); }
        }
        return $data;
    }

    public function objectFilter( $resultObj, $data ){
        $resultArr = $resultObj->toArray();
        return $this->arrayFilter($resultArr,$data);
    }

}