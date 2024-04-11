<?php
namespace domain\oauth\port\trans;

use domain\base\tran\BaseTran;
use domain\user\port\trans\UserTrans;

class OauthClientTrans extends BaseTran
{

    public function transform($result){

        $data = [
            //@data
			"id"=>(int)$result->id,
			"scope_id"=>(string)$result->scope_id,
			"client"=>(string)$result->client,
			"client_id"=>(string)$result->client_id,
			"client_secret"=>(string)$result->client_secret,
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