<?php
namespace domain\oauth\port\trans;

use domain\base\tran\BaseTran;
use domain\user\port\trans\UserTrans;

class OauthScopeTrans extends BaseTran
{

    public function transform($result){

        $data = [
            //@data
			"id"=>(int)$result->id,
			"scope"=>(string)$result->scope,
			"scope_id"=>(string)$result->scope_id,
			"create_time"=>(string)$result->create_time,
			"update_time"=>(string)$result->update_time,
			//"delete_time"=>(string)$result->delete_time,
			//@data
        ];
        $data = $this->objectFilter( $result, $data );

        /*
        $objName = 'user';
        $objectField = ['id', 'status'];
        $obj = $this->includeBelongsTo($objName, $result, $objectField, new UserTransformer());
        if (isset($obj) && $obj !== false) {
            $this->dataAfterPush($data,"status",[$objName=>$obj]);
        }
        */

        return $data;
    }

}