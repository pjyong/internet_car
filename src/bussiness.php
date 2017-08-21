<?php

use Dflydev\FigCookies\FigRequestCookies;
use Dflydev\FigCookies\FigResponseCookies;
use Dflydev\FigCookies\SetCookie as SetCookieObj;
$db = $container->db;
function getCookie( $request, $cookieName ){
    return FigRequestCookies::get($request, $cookieName)->getValue();
}

function setCookieByName($response, $name, $value, $period = 7200){
    $response = FigResponseCookies::set( $response,
        SetCookieObj::create($name)->withValue($value)->withPath('/')->withExpires( new \DateTime('+2 hours') )
    );
    return $response;
}

function getAbsoluteUrl( $extra )
{
    $host  = $_SERVER['HTTP_HOST'];
    return "http://$host/" . $extra;
}

function checkAuth( $request ){
    $host  = $_SERVER['HTTP_HOST'];
    logInfo( '###' . getCookie($request, 'id') . '验证中...' );
    if( !getCookie($request, 'id') ){
        // 获取当前页面
        $returnUrl = urlencode( 'http://' . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"] );
        header("Location: http://$host/oauth?redirect_url=".$returnUrl);
        exit;
    }

    // // 检查这个用户的状态
    $currentID = getCookie($request, 'id');
    $currentInfo = getStaffInfoByID( $currentID );
    // // 检查这个用户是否关注
    // if($currentInfo['subscribe'] == 0){
    //     header("Location: http://$host/clear");
    //     exit;
    // }

    if($currentInfo['status'] == 0 && $_SERVER["REQUEST_URI"] != '/profile/fill'
        &&  $_SERVER["REQUEST_URI"] !='/profile/save'){
        header("Location: http://$host/profile/fill");
        exit;
    } else if($currentInfo['status'] == 1 && $_SERVER["REQUEST_URI"] != '/profile'){
        header("Location: http://$host/profile");
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

function getStaffListByDepa( $depaID )
{
    global $db;
    $staffList = $db->fetchAll( 'SELECT * FROM staff WHERE department = ? and status= 2 AND id = 7', array($depaID) );

    return $staffList;
}


function insertStaff( $info )
{
    global $db;
    $db->insert( 'staff', $info );
    return $db->lastInsertId();
}

function insertIssue( $info )
{
    global $db;
    $info['create_time'] = date('Y-m-d H:i:s');
    $db->insert( 'issue', $info );
    return $db->lastInsertId();
}

function unsubscribeStaff( $openID )
{
    global $db;
    $info['subscribe'] = 0;
    $info['unsubscribe_time'] = date('Y-m-d H:i:s');
    $db->update( 'staff', $info, array('open_id'=>$openID) );
}

function saveStaffInfoByOpenID( $info, $openID )
{
    global $db;
    $db->update( 'staff', $info, array('open_id'=>$openID) );
}

// 保存用户资料
function saveStaffInfo( $info, $staffID )
{
    global $db;
    $info['status'] = 1;
    $info['create_time'] = date('Y-m-d H:i:s');
    $db->update( 'staff', $info, array('id'=>$staffID) );
}

// 审核成员
function confirmStaff( $staffID, $status, $department )
{
    global $db;
    $db->update( 'staff', array(
        'department'=>$department,
        'status' => $status,
        'department' => $department
    ), array('id'=>$staffID) );
}

// 确认预约时间
function comfirmIssue( $issueID, $serveTime, $confirmStaffID )
{
    global $db;
    $db->update( 'issue', array(
        'confirm_status'=>1,
        'confirm_time'=>date('Y-m-d H:i:s'),
        'confirm_staff_id'=>$confirmStaffID,
        'serve_no'=>getNewServeNO(),
        'serve_time'=>$serveTime,
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
        0 => '其它部门',
        1 => '技术部',
    );
}

// 检查某个人是不是技术部门的
function checkTechPeople( $staffID )
{
    $staffInfo = getStaffInfoByID($staffID);
    return checkTechDepartment( $staffInfo['department'] );
}

// 检查是否有权限做审核
function checkTechDepartment( $id )
{
    return $id == 1 ? true : false;
}

function getIssueListByStaffID( $staffID ){
    global $db;
    $allIssues = $db->fetchAll( 'SELECT * FROM issue WHERE staff_id = ? order by id desc', array( $staffID ) );
    if($allIssues){
        foreach($allIssues as $k => $v){
            if($v['image_url']){
                $images = explode(',', $v['image_url']);
                foreach($images as $k2 => $v2){
                    $images[$k2] = getAbsoluteUrl($v2);
                }
                $allIssues[$k]['image_url'] = $images;
            }
        }
    }

    return $allIssues;
}

function getIssueListByStatus( $confirmStatus )
{
    global $db;
    $allIssues = $db->fetchAll( 'SELECT * FROM issue WHERE confirm_status = ? order by id desc', array( $confirmStatus ) );
    if($allIssues){
        foreach($allIssues as $k => $v){
            if($v['image_url']){
                $images = explode(',', $v['image_url']);
                foreach($images as $k2 => $v2){
                    $images[$k2] = getAbsoluteUrl($v2);
                }
                $allIssues[$k]['image_url'] = $images;
            }
        }
    }

    return $allIssues;
}

// 获取详细信息
function getIssueDetail( $id )
{
    global $db;
    $issueInfo = $db->fetchAssoc( 'SELECT * FROM issue WHERE id = ?', array($id) );
    if($issueInfo['image_url']){
        $images = explode(',', $issueInfo['image_url']);
        foreach($images as $k2 => $v2){
            $images[$k2] = getAbsoluteUrl($v2);
        }
        $issueInfo['image_url'] = $images;
    }

    // 获取视频信息
    if($issueInfo['video_id']){
        $issueInfo['videoInfo'] = getVideoByID( $issueInfo['video_id'] );
    }

    return $issueInfo;
}

// 文件图片上传
function saveToImage( $data, $fileType = 'image/jpeg' ){

    $file = SRC_PATH . '../upload/' . date("dMYHis.");
    if( $fileType == 'image/jpeg' || $fileType == 'image/jpg' ){
        $file .= 'jpg';
    }else if( $fileType == 'image/png' ){
        $file .= 'png';
    }
    // $data = str_replace("%26", '&', $data);
    // $data = str_replace("%2B", '+', $data);
    $data = base64_decode( str_replace('data:'.$fileType.';base64,', '',$data) );
    $fp = fopen($file, 'w');
    fwrite($fp, $data);
    fclose($fp);

    return str_replace( SRC_PATH . '../', '', $file);
}

// 提取出时间
function getShortTime( $d )
{
    return date('m月d日H点左右', strtotime($d));
}


// 存储文件
function saveFile( $fileName, $fileContent )
{
    $fp = fopen(SRC_PATH . '../upload/' . $fileName, 'w');
    fwrite($fp, $fileContent);
    fclose($fp);

    return 'upload/' . $fileName;
}

// 生成签名2
function generateSignatureV2( $srcStr ){
    $secretKey = 'jKjwzrX5SddR7c0CFmDTkp9L0WCat0ve';
    $signStr = base64_encode(hash_hmac('sha1', $srcStr, $secretKey, true));
    return $signStr;
}

// 插入视频
function insertVideo( $videoInfo )
{
    global $db;
    $db->insert( 'video', $videoInfo );
    return $db->lastInsertId();
}

function updateVideo( $videoInfo, $fileID )
{
    global $db;
    $db->update( 'video', $videoInfo, array('file_id'=>$fileID) );
}

function getVideo( $fileID ){
    global $db;
    $videoInfo = $db->fetchAssoc( 'SELECT * FROM video WHERE file_id = ?', array($fileID) );
    $videoInfo['source'] = unserialize($videoInfo['source']);
    return $videoInfo;
}

function getVideoByID( $id ){
    global $db;
    $videoInfo = $db->fetchAssoc( 'SELECT * FROM video WHERE id = ?', array($id) );
    $videoInfo['source'] = unserialize($videoInfo['source']);
    return $videoInfo;
}

// 生成签名
function generateSignature( $time )
{
    // 确定APP的云API密钥
    $secret_id = "AKIDhIhzAACe6NVVKAbVwBE5kDTZTBlAmYTB";
    $secret_key = "jKjwzrX5SddR7c0CFmDTkp9L0WCat0ve";

    // 确定签名的当前时间和失效时间
    $current = $time;
    $expired = $current + 86400;  // 签名有效期：1天

    // 向参数列表填入参数
    $arg_list = array(
        "secretId" => $secret_id,
        "currentTimeStamp" => $current,
        "expireTime" => $expired,
        "random" => rand());

    // 计算签名
    $orignal = http_build_query($arg_list);
    $signature = base64_encode(hash_hmac('SHA1', $orignal, $secret_key, true).$orignal);

    return $signature;
}

// 向所有技术员发通知
// issueID, fromUserID
function sendIssueCreatedNotificationToDepa( $data )
{
    global $app;
    require_once SRC_PATH . 'wechat-php-sdk/wechat.class.php';
    $weObj = new Wechat( $app->getContainer()->get('settings')['wechat'] );
    // 获取所有已关注的技术人员
    $fromUserInfo = getStaffInfoByID();
    $allStaffs = getStaffListByDepa(1);
    foreach($allStaffs as $staff){
        $weObj->sendTemplateMessage(array(
            'touser' => $staff['open_id'],
            'template_id' => 'QR2oLdUdq06oyZQGWpAVZ0pyr61XJTY26jegWdu6hxc',
            'url' => getAbsoluteUrl('issue/detail/' . $data['issueID']),
            "topcolor":"#FF0000",
            "data":{
				"name": {
					"value": $staff['name'],
					"color":"#173177"	 //参数颜色
				}
			}

        ));
    }
}

// 指定某个人发通知
function sendNotificationToStaff()
{

}
