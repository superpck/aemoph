<?php
date_default_timezone_set("Asia/Bangkok");
error_reporting(E_ERROR | E_PARSE);
ini_set('memory_limit', '512M');

/*==========================================================================
config connect to `is` database
*/
$isDB = [
    'host' => 'localhost',
    'username' => 'isuser',
    'password' => 'password',
    'port' => '3306',
    'dbname' => 'isdb',
    'charset' => 'utf8',
    'socket' => "",
];

// config ค่าสำหรับการส่งข้อมูลเข้ากระทรวง
$mophUser = [
    'url' => 'http://ict-pher.moph.go.th:8080/v2/',
    'username' => "user",     // ระบุ username ที่ขอไว้กับระทรวง
    'password' => "password",       // ระบุ password ที่ให้ไว้กับกระทรวง
    'mophTableName' => 'is'         // ชื่อตารางข้อมูลที่บันทึกในกระทรวง
];

$backwardTime = 10; //minute, ระยะเวลาที่อ่านข้อมูลย้อนหลัง

/*========================================================================*/

// กำหนดช่วงเวลาที่ต้องการ get ข้อมูล
$isTable = $isDB["dbname"];
if (date("H:i:s") == "01:00:00" || date("H:i:s") == "06:00:00") {
    $Date1 = date("Y-m-d H:i:s", mktime(date("H"), date("i"), 0, date("m"), date("d") - 1, date("Y")));
    $sql = "select * from $isTable.`is` where adate > '$Date1 00:00:00' order by adate";
} else {
    $Date1 = date("Y-m-d H:i:s", mktime(date("H"), date("i") - $backwardTime, 0, date("m"), date("d"), date("Y")));
    $sql = "select * from $isTable.`is` where lastupdate > '$Date1' order by lastupdate";
}
$Date2 = date("Y-m-d H:i:s");

echo "Start Process: ", date("Y-m-d H:i:s"), "\n";
echo "crontab name: send `is` data to moph \n";
echo "from: ", $Date1, " to ", $Date2, "\n";

// สร้าง token เพื่อใช้ในการส่งข้อมูล
$token = getToken($mophUser);
if (!$token || $token == '') {
    echo "Can't get tokenKey\n";
    return;
}

// connect ไปยัง Mysql
$isDBconnect = new mysqli($isDB["host"], $isDB["username"], $isDB["password"], $isDB["dbname"], $isDB["port"])
    or die('Could not connect to the database server' . mysqli_connect_error());
$isDBconnect->query("SET NAMES " . $isDB["charset"]);

// อ่านข้อมูล is
$result = $isDBconnect->query($sql);
if ($result->num_rows <= 0) {
    $result->close();
    return;
}

// loop อ่านข้อมูลเพื่อส่งที่ละ record
$rw = [];
while ($row = $result->fetch_array()) {
    unset($row["ref"]);
    unset($row["lastupdate"]);
    foreach ($row as $column => $value) {
        if ($column == '0' || $column + 0 > 0) {
            unset($row[$column]);
        }
    }
    $rw[0] = $row;

    // ส่งข้อมูลไปยังกระทรวง
    send_moph($rw, $token, $mophUser);
}

// ปิด connection mysql
$isDBconnect->close();

// สั่ง expire token เพื่อป้องกันการแอบใช้งาน
expireToken($token, $mophUser);

echo "\nEnd Process: ", date("Y-m-d H:i:s"), "\n";
return;

// request send function =========================================
function send_moph($data, $token, $mophUser) {
    $request = [];
    $request["tokenKey"] = $token;
    $request["tableName"] = $mophUser['mophTableName'];
    $request["content"] = json_encode($data);

    $Req = curl_init();
    curl_setopt($Req, CURLOPT_URL, $mophUser['url'].'save');
    curl_setopt($Req, CURLOPT_POST, 1);
    curl_setopt($Req, CURLOPT_POSTFIELDS, http_build_query($request));
    curl_setopt($Req, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($Req, CURLOPT_SSL_VERIFYPEER, 0);
    $server_output = curl_exec($Req);
    curl_close($Req);

    echo $data["hn"], ' ', $data["id"], "\n";
    $ret = (array) json_decode($server_output);
    return $ret;
}

// request create token function =========================================
function getToken($mophUser) {
    $request = [];
    $request["username"] = $mophUser['username'];
    $request["password"] = $mophUser['password'];

    $Req = curl_init();
    curl_setopt($Req, CURLOPT_URL, $mophUser['url'].'create-token/');
    curl_setopt($Req, CURLOPT_POST, 1);
    curl_setopt($Req, CURLOPT_POSTFIELDS, http_build_query($request));
    curl_setopt($Req, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($Req, CURLOPT_SSL_VERIFYPEER, 0);
    $server_output = curl_exec($Req);
    curl_close($Req);

    $ret = (array) json_decode($server_output);
    return $ret["token"];
}

// request expire token function =========================================
function expireToken($token, $mophUser) {
    $Url = $mophUser['url'] . 'expire-token/' . $token;
    $request = [];
    $Req = curl_init();
    curl_setopt($Req, CURLOPT_URL, $Url);
    curl_setopt($Req, CURLOPT_POST, 1);
    curl_setopt($Req, CURLOPT_POSTFIELDS, http_build_query($request));
    curl_setopt($Req, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($Req, CURLOPT_SSL_VERIFYPEER, 0);
    $server_output = curl_exec($Req);
    curl_close($Req);

    return $server_output;
}
