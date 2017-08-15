<?php
$db = $container->db;

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

// 保存用户资料
function saveStaffInfo( $info, $staffID )
{
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
