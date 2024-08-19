<?php

function curl($url, $ifpost = 0, $datafields = '', $cookiefile = '', $v = false)
{
    $headers = [
        'Connection: Keep-Alive',
        'Accept: text/html, application/xhtml+xml, */*',
        'Pragma: no-cache',
        'Accept-Language: zh-Hans-CN,zh-Hans;q=0.8,en-US;q=0.5,en;q=0.3',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3',
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, $v);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    if ($ifpost) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $datafields);
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    // 禁用 SSL 证书验证（仅用于开发环境，不推荐在生产环境使用）
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

    if ($cookiefile) {
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiefile);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiefile);
    }
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        return 'Curl error: ' . curl_error($ch) . "\n";
    }

    // 检查 HTTP 状态码
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($httpCode === 200) ? $response : 'Error: HTTP ' . $httpCode;
}

function sendRequests($url, $count = 50)
{
    $results = [];
    for ($i = 0; $i < $count; ++$i) {
        $results[] = curl($url);
        sleep(rand(1, 3)); // 随机延时
    }
    return $results;
}

// 设置响应头以指示返回的是 JSON 格式
header('Content-Type: application/json');

// 获取输入的 JSON 数据
$data = json_decode(file_get_contents('php://input'), true);

// 检查 URL 和人气数量是否存在
if (isset($data['url']) && isset($data['popularity'])) {
    $url = $data['url'];
    $popularity = intval($data['popularity']); // 确保为整数

    // 调用 sendRequests 函数并获取结果
    $results = sendRequests($url, $popularity); // 使用用户指定的请求次数

    // 返回处理结果和成功消息，包含提交的地址和人气数量
    echo json_encode([
        'status' => 'success',
        'message' => '站点：' . htmlspecialchars($url) . '，已成功增加人气：' . $popularity . '！', // 返回提交的地址和人气数量
        'total_requests' => $popularity, // 返回用户指定的请求次数
        'results' => $results
    ]);
} else {
    // 返回错误信息
    echo json_encode([
        'status' => 'error',
        'message' => '无效的 URL 或人气数量',
    ]);
}
?>
