<?php

use Dflydev\FigCookies\FigRequestCookies;
use Dflydev\FigCookies\FigResponseCookies;
use Dflydev\FigCookies\SetCookie as SetCookieObj;

$db = $container->db;

// $cookies = Dflydev\FigCookies\Cookies::fromRequest($request);
function getCookie( $request, $cookieName ){
    return FigRequestCookies::get($request, $cookieName)->getValue();
}

function setCookieByName($response, $name, $value){
    $response = FigResponseCookies::set( $response,
        SetCookieObj::create($name)->withValue($value)->rememberForever()
    );
    return $response;
}

function getAbsoluteUrl( $extra )
{
    $host  = $_SERVER['HTTP_HOST'];
    return "http://$host/" . $extra;
}

function checkAuth( $request, $response ){
    // 从
    if( !getCookie($request, 'id') ){
        $host  = $_SERVER['HTTP_HOST'];
        header("Location: http://$host/oauth");
        exit;
    }
}

function logInfo( $logInfo )
{
    global $container;
    $container['logger']->info( $logInfo );
}

function checkUserExistsByOpenID( $openID ){
    global $db;
    $num = (int)$db->fetchColumn( 'SELECT COUNT(*) FROM staff WHERE open_id = ?', array($openID) );
    return (bool)$num;
}

function getStaffInfoByID( $id )
{
    global $db;
    $staffInfo = $db->fetchAssoc( 'SELECT * FROM staff WHERE id = ?', array($id) );
    if( $staffInfo ){
        $departmentList = getDepartmentList();
        $staffInfo['department_name'] = $departmentList[$staffInfo['department']];
    }

    return $staffInfo;
}

function getStaffIDByOpenID( $openID ){
    global $db;
    return  $db->fetchColumn( 'SELECT id FROM staff where open_id = ?', array($openID) );
}


// 根据成员ID获取未解决问题(结合comfirm时间点)
function getUnsolvedIssueByStaffID( $id )
{

}

// 根据审核状态获取员工列表
function getStaffListByStatus( $status )
{
    global $db;
    $staffList = $db->fetchAll( 'SELECT * FROM staff WHERE status = ?', array($status) );

    return $staffList;
}

function insertStaff( $info )
{
    global $db;
    $db->insert( 'staff', $info );
    return $db->lastInsertId();
}

// 保存用户资料
function saveStaffInfo( $info, $staffID )
{
    global $db;
    $db->update( 'staff', $info, array('id'=>$staffID) );
}

// 审核成员
function confirmStaff( $staffID, $department )
{
    global $db;
    $db->update( 'staff', array('status'=>2, 'department'=>$department), array('id'=>$staffID) );
}

// 确认预约时间
function comfirmIssue( $issueID, $confirmStaffID )
{
    global $db;
    $db->update( 'issue', array(
        'confirm_status'=>1,
        'confirm_time'=>date('Y-m-d H:i:s'),
        'confirm_staff_id'=>$confirmStaffID,
        'serve_no'=>getNewServeNO(),
    ), array('id'=>$issueID) );
}

// 获取预约号
function getNewServeNO(){
    global $db;
    $serveNO = (int)$db->fetchColumn( 'SELECT MAX(serve_no) FROM issue' );
    $serveNO++;
    return $serveNO;
}

// 部门列表
function getDepartmentList()
{
    return array(
        1 => '技术部',
        2 => '其它部门'
    );
}

// 检查是否有权限做审核
function checkTechDepartment( $id )
{
    return $id == 1 ? true : false;
}
