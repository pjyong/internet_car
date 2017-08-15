<?php
// Routes
/*
$app->get('/[{name}]', function ($request, $response, $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/' route");

    // Render index view
    return $this->renderer->render($response, 'index.phtml', $args);
});
*/


/*




*/

//
$app->get('/', function ($request, $response, $args) {
    // 检查该用户是否填写资料
    $staffID = 1;
    $staffInfo = getNewServeNO( );
    print_r( $staffInfo );
    return;

    // Render index view
    return $this->renderer->render($response, 'index.phtml', $args);
});

// 微信入口
$app->get('/entrance', function ($request, $response, $args) use($app) {
    require_once SRC_PATH . 'wechat-php-sdk/wechat.class.php';
    $weObj = new Wechat( $app->getContainer()->get('settings')['wechat'] );
    $weObj->valid(); //明文或兼容模式可以在接口验证通过后注释此句，但加密模式一定不能注释，否则会验证失败
    $type = $weObj->getRev()->getRevType();
    switch($type) {
    	case Wechat::MSGTYPE_TEXT:
			$weObj->text("hello, I'm wechat")->reply();
			exit;
			break;
    	case Wechat::MSGTYPE_EVENT:
            // 关注事件都是从这里开始的
            $event = $weObj->getRev()->getRevEvent();
            switch ($event['event']) {
                case Wechat::EVENT_SUBSCRIBE :
                    $this->logger->info("already trigger event subscribe");
                    // // 检查用户是否存在并创建用户
                    // $openID = $weObj->getRev()->getRevFrom();
                    // $userInfo = $weObj->getUserInfo($openID);
                    // $userInfo['openid'] = $openID;
        			// if(empty($userInfo['unionid'])) {
                    //     return $response->withHeader('Location', '/oauth');
        			// }
                    // break;
                case Wechat::EVENT_UNSUBSCRIBE :
                break;
            }

    		break;
    	case Wechat::MSGTYPE_IMAGE:
    		break;
    	default:
    		$weObj->text("help info")->reply();
    }
    exit;
});

// 微信授权
$app->get('/oauth', function ($request, $response, $args) {
    require_once SRC_PATH . 'wechat-php-sdk/wechat.class.php';
    $weObj = new Wechat( $this->getContainer()->get('settings')['wechat'] );
    $weObj->valid(); //明文或兼容模式可以在接口验证通过后注释此句，但加密模式一定不能注释，否则会验证失败
    $returnUrl = '';
    $params = '?redirect_url=' . Tools::encryption( $returnUrl );
    $redirectURI = $weObj->getOauthRedirect( 'http://partner.cheyuu.com/oauth' . $params );
    // 从cookie中检查OpenID是否有
    $openID = Tools::getCookie($this->items.'_OpenID');
    if (!empty($openID)) {													// openID 存在
        if (empty(Yii::app()->user->id))
            $this->login(1, $openID, $redirectURL);
    } else {																// openid 不存在
        if (empty($_GET['code'])){
            $this->redirect($redirectURI);
        }

        $oauth = $weObj->getOauthAccessToken();
        if (empty($oauth)){
            $this->redirect($redirectURI);
        }

        // 2个小时后重新获取accessToken
        Tools::setCookie('OpenID', $oauth["openid"], 6200);
        $rs = $this->initUser($oauth);

    }
    $this->redirect($redirectURL);
    exit;
});

// 填写相关资料,提交等待审核
$app->get('/profile/fill', function ($request, $response, $args) {
    return $this->renderer->render($response, 'fill_profile.phtml', $args);
});

// 填写预约问题的相关信息,提交等待确认预约
$app->get('/issue/fill', function ($request, $response, $args) {
    $this->logger->info("Slim-Skeleton '/' route");
    return $this->renderer->render($response, 'fill_issue.phtml', $args);
});

// 显示相关资料,正在审核或者开始预约
$app->get('/profile[/id/{id}]', function ($request, $response, $args) {
    $staffIDFromCookie = 2;
    $depaFromCookie = 1;
    $showActions = false;
    if( !empty($args['id']) ){
        $staffID = $args['id'];
    } else {
        $staffID = $staffIDFromCookie;
    }
    $staffInfo = getStaffInfoByID( $staffID );
    if(!$staffInfo || $staffInfo['status'] == 0){
        // $this->logger->info( '不存在或状态' );
        return $response->withHeader('Location', '/error');
    }
    // 1.被审核的人不是本人 2.审核人是技术部门
    if( $staffIDFromCookie != $staffID && checkTechDepartment( $depaFromCookie ) ){
        $showActions = true;
    }
    return $this->renderer->render($response, 'profile.phtml', array(
        'staffInfo' => $staffInfo,
        'showActions' => $showActions,
        'allDepartments' => getDepartmentList(),
    ));
});

// 员工列表
$app->get('/staffs', function ($request, $response, $args) {
    // 获取所有员工列表
    return $response->withJson( array('id'=>1) );
});

// 保存用户提交的审核资料
$app->post('/profile/save', function ($request, $response, $args) {
    $staffIDFromCookie = 1;
    saveStaffInfo( $request->getParams(), $staffIDFromCookie );
    return $response->withJson( array('status'=>true, 'msg'=>'操作成功') );
});

// 保存用户审核结果
$app->post('/profile/comfirm', function ($request, $response, $args) {
    confirmStaff( $args['staffID'], $args['department'] );

    return $response->withJson( array('status'=>true, 'msg'=>'操作成功') );
});

// 测试
$app->get('/test', function ($request, $response, $args) {
    return $response->withJson( array('id'=>1) );
});

// 所有的错误页面
$app->get('/error', function ($request, $response, $args) {
    return $this->renderer->render($response, 'error.phtml', array(
    ));
});
