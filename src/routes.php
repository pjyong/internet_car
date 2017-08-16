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

// 通过openID获取用户信息
$app->get('/weinfo', function ($request, $response, $args) use($app) {
    require_once SRC_PATH . 'wechat-php-sdk/wechat.class.php';
    $weObj = new Wechat( $app->getContainer()->get('settings')['wechat'] );
    print_r( $weObj->getUserInfo( 'o6NkEwoN5G300JSPB6Z8TprmPh6M' ) );
    exit;
});

// 微信授权, 添加接口配置信息时使用
$app->get('/entrance', function ($request, $response, $args) use($app) {
    require_once SRC_PATH . 'wechat-php-sdk/wechat.class.php';
    $weObj = new Wechat( $app->getContainer()->get('settings')['wechat'] );
    $weObj->valid(); //明文或兼容模式可以在接口验证通过后注释此句，但加密模式一定不能注释，否则会验证失败
});

// 微信通知入口
$app->post('/entrance', function ($request, $response, $args) use($app) {
    require_once SRC_PATH . 'wechat-php-sdk/wechat.class.php';
    $weObj = new Wechat( $app->getContainer()->get('settings')['wechat'] );
    $weObj->valid(); //明文或兼容模式可以在接口验证通过后注释此句，但加密模式一定不能注释，否则会验证失败
    $type = $weObj->getRev()->getRevType();
    switch($type) {
    	case Wechat::MSGTYPE_TEXT:
			$weObj->text("这只是个自动回复")->reply();
			break;
    	case Wechat::MSGTYPE_EVENT:
            // 关注事件都是从这里开始的
            $event = $weObj->getRev()->getRevEvent();
            switch ($event['event']) {
                case Wechat::EVENT_SUBSCRIBE :
                    // 检查用户是否存在并创建用户
                    $openID = $weObj->getRev()->getRevFrom();
                    if( !checkUserExistsByOpenID($openID) ){
                        $userInfo = $weObj->getUserInfo($openID);
                        $staffInfo = array(
                            'open_id' => $openID,
                            'nickname' => $userInfo['nickname'],
                            'sex' => $userInfo['sex'],
                            'subscribe_time' => date('Y-m-d H:i:s', $userInfo['subscribe_time']),
                            'subscribe' => 1,
                            'headimgurl' => $userInfo['headimgurl'],
                        );
                        $staffID = insertStaff( $staffInfo );
                        $this->logger->info( '刚刚有成员ID为'.$staffID.'的同学关注了' );

                        // Todo::推送一个模板消息提示告诉用户完善资料
                    }
                    break;
                case Wechat::EVENT_UNSUBSCRIBE :
                break;
            }
    		break;
    	case Wechat::MSGTYPE_IMAGE:
    		break;
    	default:
    		$weObj->text("你好")->reply();
    }
    exit;
});

// 微信授权
$app->get('/oauth', function ($request, $response, $args) use( $app ) {
    require_once SRC_PATH . 'wechat-php-sdk/wechat.class.php';
    $weObj = new Wechat( $app->getContainer()->get('settings')['wechat'] );
    $weObj->valid( true ); //明文或兼容模式可以在接口验证通过后注释此句，但加密模式一定不能注释，否则会验证失败
    $returnUrl = $request->getParam('redirect_url') ? $request->getParam('redirect_url') : getAbsoluteUrl('profile');
    $params = '?redirect_url=' . $returnUrl;
    $params .= $request->getParam('wechatfrom') ? '&wechatfrom=' . trim($request->getParam('wechatfrom')) : '';
    $redirectURI = $weObj->getOauthRedirect( 'http://partner.cheyuu.com/oauth' . $params );
    logInfo($redirectURI);
    $openID = getCookie( $request, 'openid');
    if ( $openID ) {													// openID 存在
        if ( !getCookie( $request, 'id') ){
            // 设置用户id
            // 通过openID获取id
            $staffID = getStaffIDByOpenID($openID);
            $response = setCookieByName($response, 'id', $staffID );
            return $response->withHeader('Location', $returnUrl);
        }
    } else {
        if ( !$request->getParam('code') ){
            return $response->withHeader('Location', $redirectURI);
        }
        $oauth = $weObj->getOauthAccessToken();
        if (empty($oauth)){
            return $response->withHeader('Location', $redirectURI);
        }
        $response = setCookieByName($response, 'openid', $oauth["openid"] );
        // 继续检查用户
        $staffID = getStaffIDByOpenID( $oauth["openid"] );
        if( $staffID ){
            // 如果已经存在
        }else{
            // 插入新成员记录
            $userInfo = $weObj->getUserInfo($oauth["openid"]);
            $staffInfo = array(
                'open_id' => $oauth["openid"],
                'nickname' => $userInfo['nickname'],
                'sex' => $userInfo['sex'],
                'subscribe_time' => date('Y-m-d H:i:s', $userInfo['subscribe_time']),
                'subscribe' => 1,
                'headimgurl' => $userInfo['headimgurl'],
            );
            $staffID = insertStaff( $staffInfo );
        }
        $response = setCookieByName($response, 'id', $staffID );
    }
    return $response->withHeader('Location', $returnUrl);
});

// 显示相关资料,正在审核或者开始预约
$app->get('/profile[/id/{id}]', function ($request, $response, $args) {
    checkAuth( $request );
    $staffIDFromCookie = getCookie($request, 'id');
    $showActions = false;
    if( !empty($args['id']) ){
        // 查看他人的资料
        if( $args['id'] == $staffIDFromCookie ){
            return $response->withHeader('Location', getAbsoluteUrl('profile') );
        }
        $staffInfo = getStaffInfoByID( $args['id'] );
        if(!$staffInfo || $staffInfo['status'] == 0){
            return $response->withHeader('Location', '/error');
        }
        // 获取当前登录用户的部门
        // 1.被审核的人不是本人 2.审核人是技术部门
        $currentStaffInfo = getStaffInfoByID( $staffIDFromCookie );
        $showActions = checkTechDepartment( $currentStaffInfo['department'] ) ? true : false;
        $title = '他的资料';
        $issueList = getIssueListByStaffID( $args['id'] );
    } else {
        // 查看自己的资料
        $staffInfo = getStaffInfoByID( $staffIDFromCookie );
        $title = '我的资料';
        $issueList = getIssueListByStaffID( $staffIDFromCookie );
    }

    return $this->renderer->render($response, 'profile.phtml', array(
        'title' => $title,
        'staffInfo' => $staffInfo,
        'showActions' => $showActions,
        'allDepartments' => getDepartmentList(),
        'issueList' => $issueList,
    ));
});

// 填写相关资料,提交等待审核
$app->get('/profile/fill', function ($request, $response, $args) {
    checkAuth( $request );
    return $this->renderer->render($response, 'fill_profile.phtml', array(
        'title' => '完善资料',
    ));
});
// 保存用户提交的审核资料
$app->post('/profile/save', function ($request, $response, $args) {
    checkAuth( $request );
    $staffIDFromCookie = getCookie($request, 'id');
    saveStaffInfo( $request->getParams(), $staffIDFromCookie );
    return $response->withJson( array('status'=>true, 'msg'=>'操作成功') );
});

// 保存用户审核结果
$app->post('/profile/comfirm', function ($request, $response, $args) {
    confirmStaff( $args['staffID'], $args['department'] );

    return $response->withJson( array('status'=>true, 'msg'=>'操作成功') );
});

// 所有问题列表
$app->get('/issue/stauts/[status]', function ($request, $response, $args) {
    checkAuth( $request );

    return $this->renderer->render($response, 'issue_list.phtml', $args);
});

// 问题详情
$app->get('/issue/detail/[id]', function ($request, $response, $args) {
    checkAuth( $request );
    

    return $this->renderer->render($response, 'issue_detail.phtml', $args);
});

// 问题图片上传
$app->post('/issue/image/upload', function ($request, $response, $args) {
    checkAuth( $request );
    $filepath = saveToImage( $request->getParam('base64'), $request->getParam('suffix'));

    return $response->withJson( array('status'=>true, 'msg'=>'操作成功', 'path' => $filepath) );
});

// 填写预约问题的相关信息,提交等待确认预约
$app->get('/issue/fill', function ($request, $response, $args) {
    checkAuth( $request );

    return $this->renderer->render($response, 'fill_issue.phtml', $args);
});

// 保存问题
$app->post('/issue/save', function ($request, $response, $args) {
    checkAuth( $request );
    $data = array(
        'description' => $request->getParam('description'),
        'image_url' => implode(',', $request->getParam('image_url')),
        'staff_id' => getCookie($request, 'id')
    );
    insertIssue($data);
    return $response->withJson( array('status'=>true, 'msg'=>'操作成功') );
});

// 员工列表
$app->get('/staffs', function ($request, $response, $args) {
    // 获取所有员工列表
    return $response->withJson( array('id'=>1) );
});

// 测试
$app->get('/test', function ($request, $response, $args) {
    echo urldecode('http%3A%2F%2Fpartner.cheyuu.com%2Foauth%3Fredirect_url%3Dhttp%3A%2F%2Fpartner.cheyuu.com%2Fprofile%2Ffill');exit;
    return $response->withJson( array('id'=>1) );
});

// 所有的错误页面
$app->get('/error', function ($request, $response, $args) {
    return $this->renderer->render($response, 'error.phtml', array(
    ));
});
