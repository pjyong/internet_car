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



// 填写预约问题的相关信息,提交等待确认预约
$app->get('/fill_issue', function ($request, $response, $args) {
    return $this->renderer->render($response, 'fill_profile.phtml', $args);
});
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

// 填写相关资料,提交等待审核
$app->get('/profile/fill', function ($request, $response, $args) {
    return $this->renderer->render($response, 'fill_profile.phtml', $args);
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

// 保存用户提交的审核资料
$app->post('/profile/save', function ($request, $response, $args) {
    $staffIDFromCookie = 2;

    saveStaffInfo( $args, $staffIDFromCookie );
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

$app->get('/error', function ($request, $response, $args) {
    return $this->renderer->render($response, 'error.phtml', array(
    ));
});
