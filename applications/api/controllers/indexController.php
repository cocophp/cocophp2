<?php

namespace applications\api\controllers;

class indexController{
    public function detailsAction(){
		return [
			'status' => true,
			'message'=> "Get detail's data success",
			'data'   => [
				'title' => 'emmm',
				'detail'=> 'aaaaaaaaaaaa',
			],
		];
	}
}
