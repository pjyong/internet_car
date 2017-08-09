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

// 填写相关资料,提交等待审核
$app->get('/fill_profile', function ($request, $response, $args) {
    return $this->renderer->render($response, 'fill_profile.phtml', $args);
});

// 显示相关资料,正在审核或者开始预约
$app->get('/profile', function ($request, $response, $args) {
    return $this->renderer->render($response, 'fill_profile.phtml', $args);
});

// 填写预约问题的相关信息,提交等待确认预约
$app->get('/fill_issue', function ($request, $response, $args) {
    return $this->renderer->render($response, 'fill_profile.phtml', $args);
});

//
