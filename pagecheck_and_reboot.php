<?php
/**
 * healthcheck & tomcat reboot
 *
 * @author Suzuki Koichi <sk8-mr51@bellks.com>
 * @copyright Copyright (c) 2014 Suzuki Koichi
 * @link https://github.com/mr51/tomcat_wakeup
 * @license This software is released under the MIT License, see LICENSE
 **/
$base_directory = __DIR__;

$healthcheck_url = 'http://localhost/';
$healthcheck_count = 3;
$healthcheck_timeout = 15;
$healthcheck_interval = 5;
$healthcheck_command = "wget -S -O {$base_directory}/tmp/.tmppage -T {$healthcheck_timeout} {$healthcheck_url} 2>&1";

$tomcat_shutdown_command = '/etc/init.d/tomcat stop';
$tomcat_shutdown_wait_second = 30;
$tomcat_process_check_command = 'ps aux|grep tomcat|grep java|awk \'{print $2}\'';
$tomcat_process_kill_command = 'kill -9 __PID__';
$tomcat_startup_command = '/etc/init.d/tomcat start';

$logfile = '/var/log/tomcat_healthcheck.log';
$alertmail_address_to = 'system@hoge';
$alertmail_address_from = 'system@fuga';
$alertmail_subject = '【重要】システムを再起動しました';
$alertmail_body = '__TIME__ tomcatのダウンを検知、再起動を処理を行いました';

$time = date('m/d H:i:s');

$is_system_active = false;
// healthcheck　tomcat起動中にhealthcheckが重なった場合を考慮して指定回数チェックを行う
for ($i = 0;$i < $healthcheck_count;$i++) {
    $command_response = `$healthcheck_command`;
    $is_system_active = (preg_match('/HTTP\/1.[01] 200 OK/', $command_response) > 0);
    // コンテンツの中身によって判断したい場合ここで tmp/.tmppage を確認してなんか処理する
    if ($is_system_active) {
        break;
    }
    sleep($healthcheck_interval);
}
// healthcheck に問題がなければlog出力をして終了
if ($is_system_active) {
    if (!empty($logfile)) {
        $logline = "{$time}\ttomcat running\n";
        file_put_contents($logfile, $logline, FILE_APPEND);
    }
    exit;
}
// 再起動プロセス
// まずはtomcatを落とす
$command_response = `$tomcat_shutdown_command`;
sleep($tomcat_shutdown_wait_second);

// tomcatがゾンビ化することがよくあるので、落ちたかどうかチェック
$command_response = `$tomcat_process_check_command`;
$command_response = trim($command_response);
$is_tomcat_shutdown = empty($command_response);
$tomcat_pid = $command_response;

// ゾンビ化していた場合PIDを用いてKILL
if (!$is_tomcat_shutdown) {
    $kill_command = str_replace('__PID__', $tomcat_pid, $tomcat_process_kill_command);
    $command_response = `$kill_command`;
}

// tomcat 起動
$command_response = `$tomcat_startup_command`;

// 再起動したことをメール通知
$alertmail_body_timeon = str_replace('__TIME__', $time, $alertmail_body);
$alertmail_header = "From: $alertmail_address_from\r\n";
mb_language("Ja");
mb_internal_encoding("UTF-8");
mb_send_mail($alertmail_address_to, $alertmail_subject, $alertmail_body_timeon, $alertmail_header);

if (!empty($logfile)) {
    $logline = "{$time}\ttomcat reboot!\n";
    file_put_contents($logfile, $logline, FILE_APPEND);
}
